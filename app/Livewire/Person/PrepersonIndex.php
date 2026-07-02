<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class PrepersonIndex extends Component
{
    /**
     * Render the preperson index view.
     * Purely frontend page with zero backend logic.
     */
    public function render(): View
    {
        return view('livewire.person.preperson-index');
    }
}
