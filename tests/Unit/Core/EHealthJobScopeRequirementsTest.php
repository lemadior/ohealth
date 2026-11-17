<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Auth\EHealth\Services\TokenStorage;
use App\Classes\eHealth\Api\Contract as ContractApi;
use App\Classes\eHealth\Api\Employee as EmployeeApi;
use App\Core\EHealthJob;
use App\Enums\Composition\CompositionAsyncOperation;
use App\Enums\Composition\CompositionJobStatus;
use App\Enums\JobStatus;
use App\Exceptions\EHealth\EHealthSyncScopeException;
use App\Jobs\CarePlanFullSync;
use App\Jobs\CarePlanInpatientApprovalProcessing;
use App\Jobs\CompositionAsyncProcessing;
use App\Jobs\ConnectionClientSync;
use App\Jobs\ConnectionSync;
use App\Jobs\ContractRequestDetailsUpsert;
use App\Jobs\ContractRequestSync;
use App\Jobs\ContractSync;
use App\Jobs\DivisionSync;
use App\Jobs\EmployeeDetailsUpsert;
use App\Jobs\EmployeeSync;
use App\Jobs\HealthcareServiceSync;
use App\Jobs\LegalEntityDetailsSync;
use App\Jobs\LegalEntitySync;
use App\Jobs\RemoteEHealthLinksProcessing;
use App\Livewire\Contract\ContractIndex;
use App\Livewire\Employee\EmployeeIndex;
use App\Models\Connection;
use App\Models\Contracts\ContractRequest;
use App\Models\EhealthJob as EHealthJobModel;
use App\Models\EhealthLink;
use App\Models\Employee\Employee;
use App\Models\MedicalEvents\Sql\Composition;
use App\Models\MedicalEvents\Sql\CompositionOperation;
use App\Models\User;
use App\Policies\ConnectionPolicy;
use App\Policies\ContractPolicy;
use App\Policies\EmployeePolicy;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithEHealthSyncDatabase;
use Tests\TestCase;

class EHealthJobScopeRequirementsTest extends TestCase
{
    use InteractsWithEHealthSyncDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEHealthSyncDatabase();
    }

    #[DataProvider('jobRequirements')]
    public function test_job_requires_every_scope_of_its_operation(string $class, bool $standalone, array $required): void
    {
        $job = $this->makeJob($class, $standalone);

        $this->assertTrue($job->canRunWithScopes($required));
        $this->assertFalse($job->canRunWithScopes([]));
        foreach ($required as $missing) {
            $this->assertFalse($job->canRunWithScopes(array_values(array_diff($required, [$missing]))), $missing);
        }
    }

    public static function jobRequirements(): array
    {
        return [
            'employee list and details' => [EmployeeSync::class, false, ['employee:read', 'employee:details']],
            'employee list only' => [EmployeeSync::class, true, ['employee:read']],
            'employee details only' => [EmployeeDetailsUpsert::class, true, ['employee:details']],
            'division with services' => [DivisionSync::class, false, ['division:read', 'healthcare_service:read']],
            'division only' => [DivisionSync::class, true, ['division:read']],
            'healthcare service' => [HealthcareServiceSync::class, true, ['healthcare_service:read']],
            'contracts and requests' => [ContractSync::class, false, ['contract:read', 'contract_request:read']],
            'contract requests only' => [ContractRequestSync::class, false, ['contract_request:read']],
            'contract request details' => [ContractRequestDetailsUpsert::class, true, ['contract_request:read']],
            'connections and clients' => [ConnectionSync::class, false, ['connection:read', 'client:read']],
            'connections only' => [ConnectionSync::class, true, ['connection:read']],
            'client details' => [ConnectionClientSync::class, false, ['client:read']],
            'care plan only' => [CarePlanFullSync::class, true, ['care_plan:read']],
            'inpatient approval creation and verification' => [CarePlanInpatientApprovalProcessing::class, true, ['approval:create', 'approval:read']],
        ];
    }

    public function test_care_plan_approval_scope_is_required_only_for_the_enabled_branch(): void
    {
        $job = new CarePlanFullSync($this->legalEntity, patientContext: ['care_plan_approvals' => true]);
        $this->assertFalse($job->canRunWithScopes(['care_plan:read']));
        $this->assertTrue($job->canRunWithScopes(['care_plan:read', 'approval:read']));

        $job->withFakeBatch(options: ['care_plan_approvals' => false]);
        $this->assertTrue($job->canRunWithScopes(['care_plan:read']));
    }

    public function test_composition_search_scope_is_required_only_when_create_needs_uuid_recovery(): void
    {
        $job = $this->compositionJob(CompositionAsyncOperation::CREATE);
        $this->assertFalse($job->canRunWithScopes(['composition:read']));
        $this->assertTrue($job->canRunWithScopes(['composition:read', 'composition:search']));

        $job->operation->setRelation('composition', (new Composition())->forceFill(['uuid' => 'known-composition']));
        $this->assertTrue($job->canRunWithScopes(['composition:read']));

        $job->operation->setRelation('composition', null);
        $job->operation->job->response_data = ['compositionUuid' => 'remote-result-uuid'];
        $this->assertTrue($job->canRunWithScopes(['composition:read']));

        $this->assertTrue($this->compositionJob(CompositionAsyncOperation::SIGN)->canRunWithScopes(['composition:read']));
    }

    #[DataProvider('compositeOperations')]
    public function test_partial_login_keeps_failed_payload_until_a_qualified_user_logs_in(string $class, array $partial, array $full): void
    {
        $partialUser = $this->user(1, $partial);
        $qualifiedUser = $this->user(2, $full);
        [$id, $failedUuid] = $this->failedBatch($this->makeJob($class), syncEntity: $class::ENTITY);
        $this->expectOutputRegex('/Regular login synchronization/');

        $this->login($partialUser, $partial);

        Bus::assertNothingBatched();
        Notification::assertNothingSent();
        $this->assertTrue(DB::table('job_batches')->where('id', $id)->exists());
        $this->assertTrue(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());

        $this->login($qualifiedUser, $full);

        Bus::assertBatchCount(1);
        Bus::assertBatched(fn ($batch): bool => $batch->options['user']->id === 2
            && $batch->options['token_scopes'] === $full
            && Crypt::decryptString($batch->options['token']) === 'token-2');
        $this->assertFalse(DB::table('job_batches')->where('id', $id)->exists());
        $this->assertFalse(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());
    }

    #[DataProvider('compositeOperations')]
    public function test_automatic_handover_skips_users_with_only_part_of_the_required_scopes(string $class, array $partial, array $full): void
    {
        $initiator = $this->user(1, $full);
        $this->user(2, $partial);
        $qualified = $this->user(3, $full);
        $job = $this->makeJob($class);
        $this->expectOutputRegex('/Found 1 users/');

        $selected = (function (User $user): ?User {
            return $this->getUserWithActiveSession($user);
        })->call($job, $initiator);

        $this->assertSame($qualified->id, $selected?->id);
    }

    public static function compositeOperations(): array
    {
        return [
            'employee' => [EmployeeSync::class, ['employee:read'], ['employee:read', 'employee:details']],
            'division' => [DivisionSync::class, ['division:read'], ['division:read', 'healthcare_service:read']],
            'contracts' => [ContractSync::class, ['contract:read'], ['contract:read', 'contract_request:read']],
            'connections' => [ConnectionSync::class, ['connection:read'], ['connection:read', 'client:read']],
            'client details' => [ConnectionClientSync::class, ['connection:read'], ['client:read']],
            'composition recovery' => [CompositionAsyncProcessing::class, ['composition:read'], ['composition:read', 'composition:search']],
            'inpatient approval' => [CarePlanInpatientApprovalProcessing::class, ['approval:create'], ['approval:create', 'approval:read']],
        ];
    }

    public function test_care_plan_resume_keeps_approval_requirements_and_patient_context(): void
    {
        $readOnly = $this->user(1, ['care_plan:read']);
        $qualified = $this->user(2, ['care_plan:read', 'approval:read']);
        $context = ['care_plan_approvals' => true, 'patient_care_plan_registry' => true, 'person_id' => 42, 'patient_uuid' => 'patient-uuid'];
        $job = new CarePlanFullSync($this->legalEntity, standalone: true, patientContext: $context);
        [$id, $failedUuid] = $this->failedBatch($job, syncEntity: CarePlanFullSync::ENTITY, context: $context);
        $this->expectOutputRegex('/Regular login synchronization/');

        $this->login($readOnly, ['care_plan:read']);
        $this->assertTrue(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());
        Bus::assertNothingBatched();

        $this->login($qualified, ['care_plan:read', 'approval:read']);

        Bus::assertBatched(fn ($batch): bool => $batch->options['care_plan_approvals'] === true
            && $batch->options['patient_care_plan_registry'] === true
            && $batch->options['person_id'] === 42
            && $batch->jobs[0]->patientContext === $context);
        $this->assertFalse(DB::table('job_batches')->where('id', $id)->exists());
    }

    public function test_registry_care_plan_checks_missing_approval_scope_before_sending_requests(): void
    {
        $job = new CarePlanFullSync($this->legalEntity, standalone: true, patientContext: ['patient_care_plan_registry' => true, 'care_plan_approvals' => true]);
        $job->withFakeBatch(options: ['token' => Crypt::encryptString('readonly-token'), 'token_scopes' => ['care_plan:read']]);
        $job->withFakeQueueInteractions();
        $this->mock(\App\Classes\eHealth\Api\CarePlan::class)->shouldNotReceive('withToken');

        $job->handle();

        $job->assertFailedWith(EHealthSyncScopeException::class);
        Http::assertNothingSent();
    }

    public function test_base_job_checks_non_declaration_scopes_before_sending_requests(): void
    {
        $job = new ContractSync($this->legalEntity);
        $job->withFakeBatch(options: ['token' => Crypt::encryptString('partial-token'), 'token_scopes' => ['contract:read']]);
        $job->withFakeQueueInteractions();
        $this->mock(ContractApi::class)->shouldNotReceive('withToken');
        $this->expectOutputRegex('/Processing Job: ContractSync/');

        $job->handle();

        $job->assertFailedWith(EHealthSyncScopeException::class);
        Http::assertNothingSent();
    }

    public function test_paused_composition_keeps_the_remote_operation_pending(): void
    {
        DB::unprepared('CREATE TABLE ehealth_jobs (id INTEGER PRIMARY KEY, status TEXT, response_data TEXT, created_at TEXT, updated_at TEXT)');
        DB::unprepared('CREATE TABLE composition_operations (id INTEGER PRIMARY KEY, ehealth_job_id INTEGER, composition_id INTEGER, operation TEXT, created_at TEXT, updated_at TEXT)');
        $remoteJob = EHealthJobModel::create(['status' => JobStatus::PENDING->value, 'response_data' => []]);
        $operation = CompositionOperation::create(['ehealth_job_id' => $remoteJob->id, 'operation' => CompositionAsyncOperation::CREATE]);
        $user = $this->user(1, ['composition:read']);
        $job = new CompositionAsyncProcessing($operation, $this->legalEntity);
        $job->withFakeBatch(options: [
            'token' => Crypt::encryptString('token-1'), 'user' => $user, 'token_scopes' => ['composition:read'],
        ]);
        $this->expectOutputRegex('/Job PAUSED due to error code: 403/');

        $job->failed(new EHealthSyncScopeException(['composition:search']));

        $this->assertSame(JobStatus::PENDING->value, $remoteJob->fresh()->status);
        $this->assertSame(CompositionJobStatus::PENDING->value, $operation->fresh()->status);
        Bus::assertNothingBatched();
    }

    #[DataProvider('policyScopes')]
    public function test_full_sync_policy_checks_both_permissions_and_actual_token_scopes(string $class, array $permissions, array $scopes, bool $allowed): void
    {
        app(TokenStorage::class)->storeScopes($scopes);
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('checkPermissionTo')->andReturnUsing(fn (string $ability): bool => in_array($ability, $permissions, true));

        $this->assertSame($allowed, (new $class())->sync($user)->allowed());
    }

    public static function policyScopes(): array
    {
        $cases = [];
        foreach ([EmployeePolicy::class => ['employee:read', 'employee:details'],
            ContractPolicy::class => ['contract:read', 'contract_request:read'],
            ConnectionPolicy::class => ['connection:read', 'client:read']] as $class => $full) {
            $cases[$class . ' full'] = [$class, $full, $full, true];
            foreach ($full as $scope) {
                $cases[$class . ' missing permission ' . $scope] = [$class, [$scope], $full, false];
                $cases[$class . ' missing token scope ' . $scope] = [$class, $full, [$scope], false];
            }
        }

        return $cases;
    }

    public function test_employee_action_denies_partial_scope_before_calling_api(): void
    {
        $this->actingAs($this->user(1, ['employee:read']), 'ehealth');
        app(TokenStorage::class)->storeScopes(['employee:read']);
        $this->mock(EmployeeApi::class)->shouldNotReceive('getMany');

        (new EmployeeIndex())->sync();

        $this->assertNotNull(session('error'));
        Bus::assertNothingBatched();
    }

    public function test_contract_action_denies_partial_scope_before_calling_api(): void
    {
        $this->actingAs($this->user(1, ['contract:read']), 'ehealth');
        app(TokenStorage::class)->storeScopes(['contract:read']);
        $this->mock(ContractApi::class)->shouldNotReceive('getMany');

        (new ContractIndex())->sync();

        $this->assertNotNull(session('error'));
        Bus::assertNothingBatched();
    }

    public function test_excluded_jobs_keep_their_existing_validation_mode(): void
    {
        foreach ([new RemoteEHealthLinksProcessing(new EhealthLink(), $this->legalEntity),
            new LegalEntitySync($this->legalEntity), new LegalEntityDetailsSync($this->legalEntity)] as $job) {
            $this->assertFalse($job->validatesTokenScopes());
        }
    }

    protected function makeJob(string $class, bool $standalone = false): EHealthJob
    {
        return match ($class) {
            EmployeeDetailsUpsert::class => new EmployeeDetailsUpsert(new Employee(), $this->legalEntity, standalone: $standalone),
            ContractRequestSync::class => new ContractRequestSync($this->legalEntity),
            ContractRequestDetailsUpsert::class => new ContractRequestDetailsUpsert(new ContractRequest(), $this->legalEntity, standalone: $standalone),
            ConnectionClientSync::class => new ConnectionClientSync(new Connection(), $this->legalEntity, standalone: $standalone),
            CarePlanInpatientApprovalProcessing::class => new CarePlanInpatientApprovalProcessing(new EhealthLink(), $this->legalEntity),
            CompositionAsyncProcessing::class => $this->compositionJob(CompositionAsyncOperation::CREATE),
            default => new $class($this->legalEntity, standalone: $standalone),
        };
    }

    protected function compositionJob(CompositionAsyncOperation $operation): CompositionAsyncProcessing
    {
        $model = (new CompositionOperation())->forceFill(['operation' => $operation]);
        $model->setRelation('composition', null);
        $model->setRelation('job', (new EHealthJobModel())->forceFill(['response_data' => []]));

        return new CompositionAsyncProcessing($model, $this->legalEntity);
    }
}
