<?php

declare(strict_types=1);

namespace App\Livewire\Preperson;

use App\Models\Preperson;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PrepersonIndex extends Component
{
    use WithPagination;

    public ?string $searchId = null;

    public ?string $searchName = null;

    public ?string $searchBirthDate = null;

    /**
     * Prepersons matching the applied search filters, paginated.
     *
     * @return LengthAwarePaginator
     */
    #[Computed]
    public function prepersons(): LengthAwarePaginator
    {
        $query = Preperson::query();

        if (!empty($this->searchId)) {
            $query->whereLike('external_id', '%' . trim($this->searchId) . '%');
        }

        if (!empty($this->searchName)) {
            $term = '%' . trim($this->searchName) . '%';

            $query->where(static function (Builder $subQuery) use ($term): void {
                $subQuery->whereLike('first_name', $term)
                    ->orWhereLike('last_name', $term)
                    ->orWhereLike('second_name', $term);
            });
        }

        if (!empty($this->searchBirthDate)) {
            $query->whereDate('birth_date', convertToYmd($this->searchBirthDate));
        }

        return $query->latest()->paginate(config('pagination.per_page'));
    }

    /**
     * Apply the search filters, resetting pagination to the first page.
     *
     * @return void
     */
    public function search(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all search filters and reset pagination.
     *
     * @return void
     */
    public function resetFilters(): void
    {
        $this->reset(['searchId', 'searchName', 'searchBirthDate']);
        $this->resetPage();
    }

    /**
     * Render the preperson index view.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.preperson.preperson-index', ['prepersons' => $this->prepersons]);
    }
}
