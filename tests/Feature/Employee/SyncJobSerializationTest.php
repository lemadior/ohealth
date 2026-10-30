<?php

declare(strict_types=1);

namespace Tests\Feature\Employee;

use App\Core\EHealthJob;
use App\Enums\Employee\RequestStatus;
use App\Jobs\CompleteSync;
use App\Jobs\EmployeeRequestDetailsUpsert;
use App\Jobs\Middleware\UseEHealthPermissionContext;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Tests\TestCase;

class SyncJobSerializationTest extends TestCase
{
    use DatabaseTransactions;

    private LegalEntity $legalEntity;

    private User $user;

    private EmployeeRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $typeId = DB::table('legal_entity_types')->where('name', 'PRIMARY_CARE')->value('id')
            ?? DB::table('legal_entity_types')->insertGetId(['name' => 'PRIMARY_CARE']);
        $this->legalEntity = LegalEntity::create([
            'uuid' => (string) Str::uuid(),
            'status' => 'ACTIVE',
            'legal_entity_type_id' => $typeId,
        ]);
        $this->legalEntity->load('type');
        setPermissionsTeamId($this->legalEntity->id);
        Auth::shouldUse('ehealth');

        $this->user = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => Str::uuid().'@example.com',
            'password' => 'unused',
        ]);
        $permission = Permission::findOrCreate('employee_request:read', 'ehealth');
        $this->user->permissions()->attach($permission->id, ['legal_entity_id' => $this->legalEntity->id]);
        $this->user->load(['person', 'party', 'roles', 'permissions']);

        $this->request = EmployeeRequest::create([
            'uuid' => (string) Str::uuid(),
            'legal_entity_id' => $this->legalEntity->id,
            'status' => RequestStatus::NEW,
        ]);
    }

    protected function tearDown(): void
    {
        setPermissionsTeamId(null);

        parent::tearDown();
    }

    public static function payloadFormats(): array
    {
        return ['new payload' => [false], 'already queued payload' => [true]];
    }

    #[DataProvider('payloadFormats')]
    public function test_restoring_a_resumed_chain_does_not_reload_user_permissions_for_every_step(bool $legacy): void
    {
        $jobClass = $legacy ? LegacyEmployeeRequestDetailsUpsert::class : EmployeeRequestDetailsUpsert::class;
        $job = new CompleteSync($this->legalEntity);
        for ($i = 0; $i < 25; $i++) {
            $job = new $jobClass($this->request, $this->legalEntity, $job);
        }
        $job->setUserContext($this->user);
        $command = serialize($job);
        if ($legacy) {
            // Keep the original Laravel serialization format used by jobs already in the queue.
            $command = str_replace(
                'O:'.strlen($jobClass).':"'.$jobClass.'":',
                'O:'.strlen(EmployeeRequestDetailsUpsert::class).':"'.EmployeeRequestDetailsUpsert::class.'":',
                $command
            );
        }

        $permissionQueries = 0;
        DB::listen(static function (QueryExecuted $query) use (&$permissionQueries): void {
            if (str_contains($query->sql, 'model_has_permissions') || str_contains($query->sql, 'model_has_roles')) {
                $permissionQueries++;
            }
        });

        $restored = unserialize($command);

        $this->assertSame(0, $permissionQueries, 'Deserializing future jobs must not hydrate their role/permission graphs.');
        $next = new ReflectionProperty(EHealthJob::class, 'nextEntity');
        for ($i = 0; $i < 25; $i++) {
            $this->assertInstanceOf(EmployeeRequestDetailsUpsert::class, $restored);
            $this->assertSame($this->request->id, $restored->employeeRequest->id);
            $this->assertTrue($restored->legalEntity->relationLoaded('type'));
            $this->assertSame($this->user->id, $restored->user->id);
            $this->assertFalse($restored->user->relationLoaded('permissions'));
            $this->assertFalse($restored->user->relationLoaded('roles'));
            $restored = $next->getValue($restored);
        }
        $this->assertInstanceOf(CompleteSync::class, $restored);

        // Serialization must not alter the caller's loaded permissions.
        $this->assertTrue($this->user->relationLoaded('permissions'));
    }

    public static function permissionContexts(): array
    {
        return ['permitted facility' => [true], 'another facility' => [false]];
    }

    #[DataProvider('permissionContexts')]
    public function test_permissions_are_checked_under_the_job_team_when_the_job_runs(bool $permitted): void
    {
        $legalEntity = $permitted ? $this->legalEntity : LegalEntity::create([
            'uuid' => (string) Str::uuid(),
            'status' => 'ACTIVE',
            'legal_entity_type_id' => $this->legalEntity->legalEntityTypeId,
        ]);
        $job = new EmployeeRequestDetailsUpsert($this->request, $legalEntity);
        $job->setUserContext($this->user);
        $job = unserialize(serialize($job));
        setPermissionsTeamId(null);
        Auth::shouldUse('web');

        (new UseEHealthPermissionContext())->handle($job, function (EHealthJob $job) use ($permitted): void {
            $this->assertSame($job->legalEntity->id, getPermissionsTeamId());
            $this->assertSame($permitted, $job->user->hasDirectPermission('employee_request:read', 'ehealth'));
        });

        $this->assertNull(getPermissionsTeamId());
        $this->assertSame('web', Auth::getDefaultDriver());
        $this->assertFalse($job->user->relationLoaded('permissions'));
    }
}

/** Reproduce payloads created before sync jobs stopped serializing user relationships. */
class LegacyEmployeeRequestDetailsUpsert extends EmployeeRequestDetailsUpsert
{
    use SerializesModels;
}
