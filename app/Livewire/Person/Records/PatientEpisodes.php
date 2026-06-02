<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use App\Enums\JobStatus;
use App\Jobs\EpisodeFullSync;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Episode;
use App\Repositories\MedicalEvents\Repository;
use App\Traits\BatchLegalEntityQueries;
use App\Traits\HandlesSyncBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Throwable;

class PatientEpisodes extends BasePatientComponent
{
    use BatchLegalEntityQueries;
    use HandlesSyncBatch;
    use WithPagination;

    public array $episodes = [];

    public string $syncStatus = '';

    public string $filterPeriodDateRange = '';

    public string $filterCode = '';

    public string $filterStatus = '';

    public bool $showAdditionalParams = false;

    protected array $dictionaryNames = ['eHealth/ICPC2/condition_codes'];

    public array $icd10Results = [];

    protected function initializeComponent(): void
    {
        $this->getDictionary();

        $this->syncStatus = legalEntity()->getEntityStatus(LegalEntity::ENTITY_EPISODE) ?? '';

        $this->episodes = Arr::toCamelCase(
            Episode::wherePersonId($this->personId)->withRelationships()
                ->orderByRaw('CASE WHEN ehealth_updated_at IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('ehealth_updated_at')
                ->get()
                ->toArray()
        );
    }

    #[Computed]
    public function paginatedEpisodes(): LengthAwarePaginator
    {
        $collection = collect($this->episodes);

        return new LengthAwarePaginator(
            $collection->forPage($this->getPage(), config('pagination.per_page')),
            $collection->count(),
            config('pagination.per_page'),
            $this->getPage()
        );
    }

    protected function getSyncStatus(string $entityType): ?string
    {
        return $this->syncStatus ?: null;
    }

    protected function getBatchName(string $entityType): string
    {
        return EpisodeFullSync::BATCH_NAME;
    }

    protected function getJobClass(string $entityType): string
    {
        return EpisodeFullSync::class;
    }

    protected function getEntityConstant(string $entityType): string
    {
        return LegalEntity::ENTITY_EPISODE;
    }

    protected function onSyncStatusChanged(string $entityType, JobStatus $status): void
    {
        $this->syncStatus = $status->value;
    }

    public function sync(): void
    {
        if ($this->cannotStartSync('episode')) {
            return;
        }

        if ($this->shouldResumeSync('episode')) {
            $this->handleResumeLogic('episode');

            return;
        }

        try {
            $response = EHealth::episode()->getBySearchParams(
                $this->uuid,
                ['managing_organization_id' => legalEntity()->uuid]
            );
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while synchronizing episodes');

            return;
        }

        try {
            $validatedData = $response->validate();
            Repository::episode()->syncFull($this->personId, $validatedData);
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Error while synchronizing episodes');

            return;
        }

        if ($response->isNotLast()) {
            $this->dispatchRemainingPages('episode');
        } else {
            legalEntity()->setEntityStatus(JobStatus::COMPLETED, LegalEntity::ENTITY_EPISODE);
            Session::flash('success', __('patients.messages.episodes_synced_successfully'));
        }

        $this->episodes = Arr::toCamelCase($this->formatDatesForDisplay($validatedData));
    }

    public function searchICD10(string $value): void
    {
        $this->icd10Results = DB::table('icd_10')
            ->select(['code', 'description'])
            ->where(function (Builder $query) use ($value): void {
                $query->where('code', 'ILIKE', "%$value%")
                    ->orWhere('description', 'ILIKE', "%$value%");
            })
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function resetFilters(): void
    {
        $this->filterPeriodDateRange = '';
        $this->filterCode = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function search(): void
    {
        // todo: add period params after change in frontend
        $params = array_filter([
            'code' => $this->filterCode ?: null,
            'status' => $this->filterStatus ?: null,
            'managing_organization_id' => legalEntity()->uuid
        ]);

        try {
            $response = EHealth::episode()->getBySearchParams($this->uuid, $params);
            $this->episodes = Arr::toCamelCase($this->formatDatesForDisplay($response->validate()));
            $this->resetPage();
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while searching episodes');
        }
    }

    public function render(): View
    {
        return view('livewire.person.records.episodes');
    }
}
