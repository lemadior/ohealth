<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Auth\EHealth\Services\TokenStorage;

/**
 * Dispatched right before an eHealth user is logged into the application.
 * Contains all necessary data for pre-login processing, like employee synchronization.
 */
class EHealthUserLogin
{
    use Dispatchable;
    use SerializesModels;

    public string $token = '';

    /** @var list<string> Failed batches eligible for this login, preserved across queue retries. */
    public array $failedSyncBatchIds = [];

    /**
     * @param  User  $user  The user model.
     * @param  LegalEntity  $legalEntity  The legal entity context.
     * @param  string  $authUserUUID  The user's UUID from the eHealth token.
     */
    public function __construct(
        public User $user,
        public LegalEntity $legalEntity,
        public string $authUserUUID,
        public array $scopes,
        public bool $isFirstLogin = false,
        public string $guard = 'ehealth'
    ) {
        $this->token = Crypt::encryptString(app(TokenStorage::class)->getBearerToken());

        if (!$this->isFirstLogin) {
            $this->failedSyncBatchIds = DB::table('job_batches')
                ->where('legal_entity_id', $this->legalEntity->id)
                ->where('failed_jobs', '>', 0)
                ->orderBy('cancelled_at')
                ->pluck('id')
                ->all();
        }
    }
}
