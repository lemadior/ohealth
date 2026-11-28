<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Console\Commands\Install;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class FirstRunScopesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'permission.cache.store' => 'array',
            'ehealth.test.client_id' => 'b9ea75b0-0788-4a60-b91c-dc6d0533d982',
            'ehealth.test.client_secret' => 'test-secret',
            'auth.guards.sanctum' => ['driver' => 'sanctum', 'provider' => 'users'],
        ]);

        if (DB::connection()->getDriverName() !== 'pgsql'
            || DB::selectOne('SELECT current_database() AS name')->name !== 'testing') {
            throw new RuntimeException('First-run tests require the PostgreSQL testing database.');
        }

        DB::beginTransaction();
        $schema = 'first_run_' . bin2hex(random_bytes(8));
        DB::statement('CREATE SCHEMA "' . $schema . '"');
        DB::statement('SET LOCAL search_path TO "' . $schema . '"');

        $this->assertSame(0, Artisan::call('migrate', [
            '--path' => 'database/migrations/install',
            '--force' => true,
        ]));
    }

    protected function tearDown(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                DB::rollBack(0);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_initial_scopes_allow_the_seeded_owner_to_authorize(): void
    {
        $this->assertSame(0, Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TestUserMigrate', '--force' => true]));

        foreach (['api', 'sanctum'] as $guard) {
            $this->assertSame(0, DB::table('roles')->where('guard_name', $guard)->count());
            $this->assertSame(0, DB::table('permissions')->where('guard_name', $guard)->count());
        }

        $user = User::where('email', 'vitaliybezsh+fop@gmail.com')->firstOrFail();
        $entityId = DB::table('legal_entities')->where('uuid', config('ehealth.test.client_id'))->value('id');
        setPermissionsTeamId($entityId);

        $expected = array_values(array_intersect(
            config('ehealth.roles.OWNER'),
            config('ehealth.legal_entity_types.PRIMARY_CARE')
        ));
        $this->assertNotEmpty($expected);

        foreach (['web', 'ehealth'] as $guard) {
            Auth::shouldUse($guard);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->assertTrue($user->hasRole('OWNER', $guard));
            $this->assertEqualsCanonicalizing($expected, explode(' ', $user->getScopes()));
        }
    }

    public function test_seeder_reports_a_missing_owner_role_and_saves_no_test_data(): void
    {
        DB::table('roles')->where('name', 'OWNER')->where('guard_name', 'ehealth')->delete();

        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TestUserMigrate', '--force' => true]);
            $this->fail('The seeder should reject a missing OWNER role.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('OWNER', $exception->getMessage());
            $this->assertStringContainsString('ehealth', $exception->getMessage());
        }

        $this->assertSame(0, DB::table('legal_entities')->count());
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_sequence_reset_handles_empty_and_populated_tables(): void
    {
        foreach (['sequence_install' => new Install(), 'sequence_seeder' => new DatabaseSeeder()] as $table => $subject) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
            });
            $reset = new ReflectionMethod($subject, 'fixPostgresSequence');

            // Reset a previously used sequence after all records have been deleted.
            DB::table($table)->insertGetId(['name' => 'deleted']);
            DB::table($table)->delete();
            $reset->invoke($subject, $table);
            $this->assertSame(1, DB::table($table)->insertGetId(['name' => 'first']));

            // Explicit IDs do not advance the sequence automatically.
            DB::table($table)->insert(['id' => 42, 'name' => 'imported']);
            $reset->invoke($subject, $table);
            $this->assertSame(43, DB::table($table)->insertGetId(['name' => 'next']));
        }
    }
}
