<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Auth\EHealth\Services\TokenStorage;
use App\Jobs\PartyVerificationSync;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Services\Party\PartyVerificationCache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;
use Livewire\WithPagination;

class PartyVerificationIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public LegalEntity $legalEntity;

    public string $dracsDeathStatus = '';

    public bool $isSyncing = false;

    public function updatedDracsDeathStatus(): void
    {
        $this->resetPage();
    }

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;
    }

    public function sync(): void
    {
        $this->authorize('syncVerification', Party::class);

        if ($this->isSyncing) {
            return;
        }

        $user = Auth::user();
        $tokenScopes = app(TokenStorage::class)->getTokenScopes();

        if (!$user || !in_array(PartyVerificationSync::SCOPE_REQUIRED, $tokenScopes, true)) {
            session()->flash('error', __('party_verification.messages.sync_requires_read_scope'));

            return;
        }

        $this->isSyncing = true;

        try {
            $token = session()->get(config('ehealth.api.oauth.bearer_token'));

            if (!$token) {
                session()->flash('error', __('party_verification.messages.sync_requires_ehealth_session'));

                return;
            }

            Bus::batch([new PartyVerificationSync($this->legalEntity, standalone: true)])
                ->name('Party Verification Status Sync')
                ->withOption('legal_entity_id', $this->legalEntity->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->withOption('sync_entity', LegalEntity::ENTITY_PARTY_VERIFICATION)
                ->onQueue('sync')
                ->dispatch();

            session()->flash('success', __('party_verification.messages.sync_queued'));
        } finally {
            $this->isSyncing = false;
        }
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
    {
        $localItems = $this->localVerificationItems();

        if (!empty($this->dracsDeathStatus)) {
            $localItems = $localItems->filter(
                fn (array $item) => ($item['details']['dracs_death']['verification_status'] ?? null) === $this->dracsDeathStatus
            )->values();
        }

        $perPage = 50;
        $total = $localItems->count();
        $pageItems = $localItems
            ->slice(($this->getPage() - 1) * $perPage, $perPage)
            ->values();

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $this->getPage(),
            ['path' => request()->url()]
        );

        return view('livewire.party.party-verification-index', [
            'verifications' => $paginator,
        ]);
    }

    /**
     * Local parties for the current legal entity (list source).
     * Stream statuses come from cache after manual sync, otherwise from local verification_status.
     */
    private function localVerificationItems(): Collection
    {
        return Party::query()
            ->whereHas(
                'employees',
                fn ($query) => $query->where('legal_entity_id', $this->legalEntity->id)
            )
            ->whereNotNull('uuid')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (Party $party) {
                $cached = PartyVerificationCache::get($party->uuid);
                if (is_array($cached)) {
                    return [
                        'party_id' => $party->uuid,
                        'party_name' => $party->fullName,
                        'local_id' => $party->id,
                        'verification_status' => $cached['verification_status'],
                        'details' => $cached['details'],
                    ];
                }

                $status = $party->verification_status ?: '-';

                return [
                    'party_id' => $party->uuid,
                    'party_name' => $party->fullName,
                    'local_id' => $party->id,
                    'verification_status' => $status,
                    'details' => [
                        'drfo' => ['verification_status' => $status],
                        'dracs_death' => ['verification_status' => $status],
                        'dms_passport' => ['verification_status' => $status],
                    ],
                ];
            })
            ->values();
    }
}
