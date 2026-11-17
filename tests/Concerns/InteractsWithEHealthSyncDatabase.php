<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Core\EHealthJob;
use App\Enums\JobStatus;
use App\Events\EHealthUserLogin;
use App\Jobs\DivisionSync;
use App\Listeners\OnRegularLoginSyncronization;
use App\Models\LegalEntity;
use App\Models\User;
use App\Policies\DeclarationPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithEHealthSyncDatabase
{
    protected LegalEntity $legalEntity;

    protected function setUpEHealthSyncDatabase(): void
    {
        // Exercise real session queries, permission filtering and failed-payload transactions in isolation.
        config([
            'cache.default' => 'array',
            'permission.cache.store' => 'array',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'queue.batching.database' => 'sqlite',
        ]);
        DB::purge('sqlite');
        Auth::shouldUse('ehealth');
        Bus::fake();
        Notification::fake();
        Http::preventStrayRequests();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId(1);

        $this->createSchema();
        DB::table('legal_entities')->insert([
            'id' => 1, 'uuid' => 'test-entity', 'status' => 'ACTIVE', 'legal_entity_type_id' => 1,
            'sync_status' => JobStatus::COMPLETED->value, 'declaration_sync_status' => JobStatus::PAUSED->value,
        ]);
        DB::table('legal_entity_types')->insert(['id' => 1, 'name' => 'PRIMARY_CARE']);
        foreach ([...DeclarationPolicy::SYNC_SCOPES, DivisionSync::SCOPE_REQUIRED,
            'healthcare_service:read', 'employee:read', 'employee:details',
            'contract:read', 'contract_request:read', 'connection:read', 'client:read',
            'care_plan:read', 'approval:read', 'approval:create', 'composition:read', 'composition:search'] as $index => $scope) {
            DB::table('permissions')->insert(['id' => $index + 1, 'name' => $scope, 'guard_name' => 'ehealth']);
            DB::table('legal_entity_type_permissions')->insert(['permission_id' => $index + 1, 'legal_entity_type_id' => 1]);
        }
        $this->legalEntity = LegalEntity::findOrFail(1);
    }

    protected function login(User $user, array $scopes): void
    {
        session()->put(config('ehealth.api.oauth.bearer_token'), 'token-' . $user->id);
        (new OnRegularLoginSyncronization())->handle(new EHealthUserLogin($user, $this->legalEntity, 'test-user', $scopes));
    }

    protected function failedBatch(EHealthJob $job, ?string $name = null, string $syncEntity = LegalEntity::ENTITY_DECLARATION, array $context = []): array
    {
        $id = fake()->uuid();
        $failedUuid = fake()->uuid();
        $job->withBatchId($id);
        DB::table('failed_jobs')->insert([
            'uuid' => $failedUuid, 'payload' => json_encode(['data' => ['command' => serialize($job)]]),
        ]);
        DB::table('job_batches')->insert([
            'id' => $id, 'name' => $name ?? $job::BATCH_NAME, 'legal_entity_id' => 1,
            'total_jobs' => 1, 'pending_jobs' => 1, 'failed_jobs' => 1,
            'failed_job_ids' => json_encode([$failedUuid]),
            'options' => serialize(['sync_entity' => $syncEntity, ...$context]), 'created_at' => time(), 'cancelled_at' => time(),
        ]);

        return [$id, $failedUuid];
    }

    protected function user(int $id, array $scopes, ?array $permissions = null): User
    {
        DB::table('users')->insert(['id' => $id, 'party_id' => $id, 'session_id' => 'session-' . $id]);
        DB::table('employees')->insert(['id' => $id, 'legal_entity_id' => 1]);
        DB::table('employee_users')->insert(['user_id' => $id, 'employee_id' => $id]);
        DB::table('sessions')->insert([
            'id' => 'session-' . $id, 'user_id' => $id,
            'payload' => base64_encode(serialize([
                config('ehealth.api.oauth.bearer_token') => 'token-' . $id,
                config('ehealth.api.oauth.token_scopes') => $scopes,
                'auth_token_expires_at' => time() + 3600, 'ehealth_legal_entity_uuid' => $this->legalEntity->uuid,
            ])),
        ]);
        foreach (DB::table('permissions')->whereIn('name', $permissions ?? $scopes)->pluck('id') as $permissionId) {
            DB::table('model_has_permissions')->insert([
                'permission_id' => $permissionId, 'model_type' => User::class, 'model_id' => $id, 'legal_entity_id' => 1,
            ]);
        }

        return User::findOrFail($id);
    }

    protected function createSchema(): void
    {
        foreach ([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, party_id INTEGER, session_id TEXT)',
            'CREATE TABLE employees (id INTEGER PRIMARY KEY, legal_entity_id INTEGER)',
            'CREATE TABLE employee_users (user_id INTEGER, employee_id INTEGER)',
            'CREATE TABLE sessions (id TEXT PRIMARY KEY, user_id INTEGER, payload TEXT)',
            'CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT, legal_entity_id INTEGER)',
            'CREATE TABLE permissions (id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT)',
            'CREATE TABLE model_has_roles (role_id INTEGER, model_type TEXT, model_id INTEGER, legal_entity_id INTEGER)',
            'CREATE TABLE model_has_permissions (permission_id INTEGER, model_type TEXT, model_id INTEGER, legal_entity_id INTEGER)',
            'CREATE TABLE role_has_permissions (permission_id INTEGER, role_id INTEGER)',
            'CREATE TABLE legal_entities (id INTEGER PRIMARY KEY, uuid TEXT, status TEXT, legal_entity_type_id INTEGER, sync_status TEXT, declaration_sync_status TEXT, employee_sync_status TEXT, division_sync_status TEXT, hcs_sync_status TEXT, contract_sync_status TEXT, contract_request_sync_status TEXT, connection_sync_status TEXT, care_plan_sync_status TEXT, updated_at TEXT)',
            'CREATE TABLE legal_entity_types (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE legal_entity_type_permissions (permission_id INTEGER, legal_entity_type_id INTEGER)',
            'CREATE TABLE failed_jobs (id INTEGER PRIMARY KEY, uuid TEXT, payload TEXT)',
            'CREATE TABLE job_batches (id TEXT PRIMARY KEY, name TEXT, legal_entity_id INTEGER, total_jobs INTEGER, pending_jobs INTEGER, failed_jobs INTEGER, failed_job_ids TEXT, options TEXT, created_at INTEGER, cancelled_at INTEGER, finished_at INTEGER)',
        ] as $sql) {
            DB::unprepared($sql);
        }
    }
}
