<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Auth\EHealth\Services\TokenStorage;
use App\Classes\eHealth\Api\Person as PersonApi;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Enums\JobStatus;
use App\Exceptions\EHealth\EHealthSyncScopeException;
use App\Jobs\ConfidantPersonSync;
use App\Jobs\DeclarationDetailsSync;
use App\Jobs\DeclarationRequestDetailsSync;
use App\Jobs\DeclarationRequestsSync;
use App\Jobs\DeclarationsSync;
use App\Jobs\DivisionSync;
use App\Jobs\PersonAuthMethodSync;
use App\Models\Declaration;
use App\Models\DeclarationRequest;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Relations\ConfidantPerson;
use App\Models\User;
use App\Notifications\SyncNotification;
use App\Policies\DeclarationPolicy;
use App\Traits\BatchLegalEntityQueries;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;
use Tests\TestCase;

class EHealthDeclarationSyncScopesTest extends TestCase
{
    use \Tests\Concerns\InteractsWithEHealthSyncDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEHealthSyncDatabase();
    }

    #[DataProvider('declarationJobsAndScopes')]
    public function test_every_declaration_step_requires_all_chain_scopes(string $class, array $scopes, bool $allowed): void
    {
        $this->assertSame($allowed, $this->declarationJob($class)->canRunWithScopes($scopes));
    }

    public static function declarationJobsAndScopes(): array
    {
        $cases = [];
        foreach ([DeclarationsSync::class, DeclarationRequestsSync::class, DeclarationDetailsSync::class,
            DeclarationRequestDetailsSync::class, PersonAuthMethodSync::class, ConfidantPersonSync::class] as $class) {
            for ($mask = 0; $mask < 8; $mask++) {
                $scopes = array_values(array_filter(
                    DeclarationPolicy::SYNC_SCOPES,
                    fn (string $scope, int $index): bool => ($mask & (1 << $index)) !== 0,
                    ARRAY_FILTER_USE_BOTH
                ));
                $cases[$class . ' / ' . $mask] = [$class, $scopes, $mask === 7];
            }
        }

        return $cases;
    }

    public function test_declaration_handover_skips_owner_without_patient_scope(): void
    {
        $initiator = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        $this->user(2, ['declaration:read', 'declaration_request:read']);
        $doctor = $this->user(3, DeclarationPolicy::SYNC_SCOPES);
        $job = $this->selectionJob();
        $this->expectOutputRegex('/Found 1 users/');

        $this->assertSame($doctor->id, $job->pick($initiator)?->id);
    }

    public function test_handover_skips_person_reader_without_declaration_scopes(): void
    {
        $initiator = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        $this->user(2, ['person:read']);
        $this->expectOutputRegex('/Found 0 users/');

        $this->assertNull($this->selectionJob()->pick($initiator));
    }

    public function test_complete_session_scopes_do_not_replace_missing_user_permissions(): void
    {
        $initiator = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        $this->user(2, DeclarationPolicy::SYNC_SCOPES, ['declaration:read', 'declaration_request:read']);
        $this->expectOutputRegex('/Found 1 users/');

        $this->assertNull($this->selectionJob()->pick($initiator));
    }

    public function test_owner_login_preserves_failed_payload_and_does_not_report_resume(): void
    {
        $owner = $this->user(1, ['declaration:read', 'declaration_request:read']);
        [$batchId, $failedUuid] = $this->failedBatch(new PersonAuthMethodSync(new Person(), $this->legalEntity));
        $this->expectOutputRegex('/Regular login synchronization/');

        $this->login($owner, config('ehealth.roles.OWNER'));

        Bus::assertNothingBatched();
        Notification::assertNothingSent();
        $this->assertTrue(DB::table('job_batches')->where('id', $batchId)->exists());
        $this->assertTrue(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());
        $this->assertSame(JobStatus::PAUSED->value, $this->legalEntity->fresh()->declaration_sync_status);
    }

    public function test_doctor_login_resumes_saved_job_with_captured_token_scopes_and_new_user(): void
    {
        $doctor = $this->user(2, DeclarationPolicy::SYNC_SCOPES);
        $job = new PersonAuthMethodSync(new Person(), $this->legalEntity);
        $job->user = $this->user(1, ['declaration:read', 'declaration_request:read']);
        [$batchId, $failedUuid] = $this->failedBatch($job);
        // Queued login listeners have no browser session scopes.
        session()->forget(config('ehealth.api.oauth.token_scopes'));
        $this->expectOutputRegex('/Dispatched new batch/');

        $this->login($doctor, DeclarationPolicy::SYNC_SCOPES);

        Bus::assertBatched(fn ($batch): bool => $batch->options['token_scopes'] === DeclarationPolicy::SYNC_SCOPES
            && $batch->options['user']->id === $doctor->id
            && $batch->jobs[0]->user === null
            && Crypt::decryptString($batch->options['token']) === 'token-2');
        Notification::assertSentTo($doctor, SyncNotification::class);
        $this->assertFalse(DB::table('job_batches')->where('id', $batchId)->exists());
        $this->assertFalse(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());
    }

    public function test_owner_can_resume_other_entities_while_declaration_jobs_stay_saved(): void
    {
        $owner = $this->user(1, ['declaration:read', 'declaration_request:read', 'division:read']);
        [$declarationBatch, $declarationJob] = $this->failedBatch(new DeclarationsSync($this->legalEntity));
        [$divisionBatch] = $this->failedBatch(new DivisionSync($this->legalEntity));
        $this->expectOutputRegex('/Dispatched new batch/');

        $this->login($owner, config('ehealth.roles.OWNER'));

        Bus::assertBatchCount(1);
        Bus::assertBatched(fn ($batch): bool => $batch->jobs[0] instanceof DivisionSync);
        $this->assertTrue(DB::table('job_batches')->where('id', $declarationBatch)->exists());
        $this->assertTrue(DB::table('failed_jobs')->where('uuid', $declarationJob)->exists());
        $this->assertFalse(DB::table('job_batches')->where('id', $divisionBatch)->exists());
    }

    public function test_rejected_first_login_resume_does_not_mark_overall_sync_processing(): void
    {
        $owner = $this->user(1, ['declaration:read', 'declaration_request:read']);
        $this->legalEntity->setEntityStatus(JobStatus::PAUSED);
        $this->failedBatch(new PersonAuthMethodSync(new Person(), $this->legalEntity), 'FirstLoginSync', '');
        $this->expectOutputRegex('/Regular login synchronization/');

        $this->login($owner, config('ehealth.roles.OWNER'));

        $this->assertSame(JobStatus::PAUSED->value, $this->legalEntity->fresh()->sync_status);
        Bus::assertNothingBatched();
        Notification::assertNothingSent();
    }

    public function test_manual_resume_uses_current_token_scopes_and_preserves_unusable_batch(): void
    {
        $owner = $this->user(1, ['declaration:read', 'declaration_request:read']);
        [$batchId, $failedUuid] = $this->failedBatch(new DeclarationsSync($this->legalEntity));
        app(TokenStorage::class)->storeScopes(['declaration:read', 'declaration_request:read']);
        $restarter = new class
        {
            use BatchLegalEntityQueries;

            public function resume(stdClass $batch, User $user, LegalEntity $legalEntity): bool
            {
                return $this->restartBatch($batch, $user, Crypt::encryptString('token-1'), $legalEntity);
            }
        };

        $this->assertFalse($restarter->resume(DB::table('job_batches')->find($batchId), $owner, $this->legalEntity));

        $this->assertTrue(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());
        $this->assertTrue(DB::table('job_batches')->where('id', $batchId)->exists());
        Bus::assertNothingBatched();
    }

    public function test_dispatch_error_preserves_failed_payload_and_paused_status(): void
    {
        $doctor = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        [$batchId, $failedUuid] = $this->failedBatch(new DeclarationsSync($this->legalEntity));
        app(TokenStorage::class)->storeScopes(DeclarationPolicy::SYNC_SCOPES);
        $restarter = new class
        {
            use BatchLegalEntityQueries;

            public function resume(stdClass $batch, User $user, LegalEntity $legalEntity): bool
            {
                return $this->restartBatch(
                    $batch,
                    $user,
                    Crypt::encryptString('token-1'),
                    $legalEntity,
                    fn () => $legalEntity->setEntityStatus(JobStatus::PROCESSING, LegalEntity::ENTITY_DECLARATION)
                );
            }

            protected function restartFailedBatch(User $user, stdClass $batch, string $token, LegalEntity $legalEntity, array $pendingJobs, ?array $tokenScopes = null): void
            {
                throw new RuntimeException('dispatch failed');
            }
        };

        try {
            $restarter->resume(DB::table('job_batches')->find($batchId), $doctor, $this->legalEntity);
            $this->fail('The simulated dispatch error must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('dispatch failed', $exception->getMessage());
        }

        $this->assertTrue(DB::table('failed_jobs')->where('uuid', $failedUuid)->exists());
        $this->assertTrue(DB::table('job_batches')->where('id', $batchId)->exists());
        $this->assertSame(JobStatus::PAUSED->value, $this->legalEntity->fresh()->declaration_sync_status);
    }

    #[DataProvider('scopeSnapshotModes')]
    public function test_old_and_new_owner_jobs_fail_locally_before_any_api_request(bool $hasSnapshot): void
    {
        $scopes = ['declaration:read', 'declaration_request:read'];
        $owner = $this->user(1, $scopes);
        $job = new PersonAuthMethodSync(new Person(), $this->legalEntity);
        $this->mock(PersonApi::class)->shouldNotReceive('withToken');
        $options = ['user' => $owner, 'token' => Crypt::encryptString('token-1')];
        if ($hasSnapshot) {
            $options['token_scopes'] = $scopes;
        }
        $job->withFakeBatch(options: $options);
        $job->withFakeQueueInteractions();
        $job->middleware();
        $this->expectOutputRegex('/Processing Job: PersonAuthMethodSync/');

        $job->handle();

        $job->assertFailedWith(EHealthSyncScopeException::class);
        Http::assertNothingSent();
        Bus::assertNothingBatched();
    }

    public static function scopeSnapshotModes(): array
    {
        return ['legacy batch' => [false], 'captured scopes' => [true]];
    }

    public function test_legacy_batch_does_not_borrow_scopes_from_a_new_token(): void
    {
        $doctor = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        $job = new DeclarationsSync($this->legalEntity);
        $job->withFakeBatch(options: ['user' => $doctor, 'token' => Crypt::encryptString('old-token')]);
        $job->withFakeQueueInteractions();
        $job->middleware();
        $this->expectOutputRegex('/Processing Job: DeclarationsSync/');

        $job->handle();

        $job->assertFailedWith(EHealthSyncScopeException::class);
        Http::assertNothingSent();
    }

    public function test_legacy_scope_failure_can_continue_with_the_same_users_new_token(): void
    {
        $doctor = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        $job = new PersonAuthMethodSync(new Person(), $this->legalEntity);
        $job->withFakeBatch(options: ['user' => $doctor, 'token' => Crypt::encryptString('old-token')]);
        $this->expectOutputRegex('/Retrying with new user: 1/');

        $job->failed(new EHealthSyncScopeException(DeclarationPolicy::SYNC_SCOPES));

        Bus::assertBatched(fn ($batch): bool => $batch->options['user']->id === $doctor->id
            && Crypt::decryptString($batch->options['token']) === 'token-1'
            && $batch->options['token_scopes'] === DeclarationPolicy::SYNC_SCOPES);
    }

    public function test_captured_scopes_allow_execution_before_session_flush_and_reach_next_job(): void
    {
        $doctor = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        DB::table('sessions')->delete();
        $next = new PersonAuthMethodSync(new Person(), $this->legalEntity);
        $job = Mockery::mock(DeclarationsSync::class . '[sendRequest,processResponse,getNextEntityJob]', [$this->legalEntity])
            ->shouldAllowMockingProtectedMethods();
        $response = Mockery::mock(EHealthResponse::class);
        $response->shouldReceive('json')->once()->andReturn([]);
        $response->shouldReceive('isNotLast')->once()->andReturnFalse();
        $job->shouldReceive('sendRequest')->once()->with('token-1')->andReturn($response);
        $job->shouldReceive('processResponse')->once()->with($response);
        $job->shouldReceive('getNextEntityJob')->once()->andReturn($next);
        $job->withFakeBatch(options: [
            'user' => $doctor, 'token' => Crypt::encryptString('token-1'),
            'token_scopes' => DeclarationPolicy::SYNC_SCOPES, 'sync_entity' => LegalEntity::ENTITY_DECLARATION,
        ]);
        $job->withFakeQueueInteractions();
        $job->middleware();
        $this->expectOutputRegex('/Scheduling next job: PersonAuthMethodSync/');

        $job->handle();

        $job->assertNotFailed();
        Bus::assertBatched(fn ($batch): bool => $batch->jobs[0] === $next
            && $batch->options['token_scopes'] === DeclarationPolicy::SYNC_SCOPES);
    }

    #[DataProvider('retryableStatuses')]
    public function test_rejected_tokens_are_not_reused_after_automatic_handover(int $status): void
    {
        $first = $this->user(1, DeclarationPolicy::SYNC_SCOPES);
        $this->user(2, DeclarationPolicy::SYNC_SCOPES);
        $job = new PersonAuthMethodSync(new Person(), $this->legalEntity);
        $job->withFakeBatch(options: [
            'user' => $first, 'token' => Crypt::encryptString('token-1'),
            'sync_entity' => LegalEntity::ENTITY_DECLARATION,
        ]);
        $this->expectOutputRegex('/Job PAUSED due to error code: ' . $status . '/');

        $job->failed(new RuntimeException('Request rejected', $status));
        $retry = Bus::batched(fn ($batch): bool => true)->sole();
        $this->assertSame('token-2', Crypt::decryptString($retry->options['token']));
        $this->assertSame([hash('sha256', 'token-1')], $retry->options['rejected_tokens']);
        $this->assertSame(DeclarationPolicy::SYNC_SCOPES, $retry->options['token_scopes']);
        $job = $retry->jobs[0];
        $job->withFakeBatch(options: $retry->options);
        $job->failed(new RuntimeException('Request rejected', $status));

        Bus::assertBatchCount(1);
        $this->assertSame(JobStatus::PAUSED->value, $this->legalEntity->fresh()->declaration_sync_status);
    }

    public static function retryableStatuses(): array
    {
        return ['revoked token' => [401], 'forbidden' => [403], 'rate limit' => [429]];
    }

    public function test_missing_scopes_pause_first_login_without_retrying_owner_or_partial_reader(): void
    {
        $owner = $this->user(1, ['declaration:read', 'declaration_request:read']);
        $this->user(2, ['person:read']);
        $job = new PersonAuthMethodSync(new Person(), $this->legalEntity);
        $job->withFakeBatch(options: ['user' => $owner, 'token' => Crypt::encryptString('token-1'), 'sync_entity' => '']);
        $this->expectOutputRegex('/Job PAUSED due to error code: 403/');

        $job->failed(new EHealthSyncScopeException(['person:read']));

        Bus::assertNothingBatched();
        $this->assertSame(JobStatus::PAUSED->value, $this->legalEntity->fresh()->sync_status);
        $this->assertSame(JobStatus::PAUSED->value, $this->legalEntity->fresh()->declaration_sync_status);
    }

    protected function declarationJob(string $class): EHealthJob
    {
        return match ($class) {
            DeclarationDetailsSync::class => new DeclarationDetailsSync(new Declaration(), $this->legalEntity),
            DeclarationRequestDetailsSync::class => new DeclarationRequestDetailsSync(new DeclarationRequest(), $this->legalEntity),
            PersonAuthMethodSync::class => new PersonAuthMethodSync(new Person(), $this->legalEntity),
            ConfidantPersonSync::class => new ConfidantPersonSync(new ConfidantPerson(), $this->legalEntity),
            default => new $class($this->legalEntity),
        };
    }

    protected function selectionJob(): DeclarationsSync
    {
        return new class ($this->legalEntity) extends DeclarationsSync
        {
            public function pick(User $user): ?User
            {
                return $this->getUserWithActiveSession($user);
            }
        };
    }

}
