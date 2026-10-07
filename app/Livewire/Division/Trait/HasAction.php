<?php

declare(strict_types=1);

namespace App\Livewire\Division\Trait;

use Exception;
use Throwable;
use App\Enums\Status;
use App\Models\Division;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Enums\Division\Status as DivisionStatus;
use App\Enums\Equipment\Status as EquipmentStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Enums\HealthcareService\Status as HealthcareServiceStatus;

trait HasAction
{
    /**
     * Set 'ACTIVE' action status for specified division
     *
     * @param  int  $divisionId
     * @return void
     * @throws Exception|EHealthResponseException
     */
    public function activate(int $divisionId): void
    {
        $division = $this->getDivision($divisionId);

        if (!$division) {
            return;
        }

        if (Auth::user()->cannot('activate', $division)) {
            session()->flash('error', __('divisions.policy.deny.activate'));

            return;
        }

        try {
            $response = EHealth::division()->activate($division->uuid);

            session()->flash('success', __('divisions.request.activated'));
        } catch (EHealthResponseException $err) {
            $err->handle(static::class . ':activateDivision:', __('divisions.errors.activate'));

            return;
        }

        $responseData = $response->getData();

        try {
            Repository::division()->setAction($division, $responseData['status']);

            // Refresh model and sync local state
            $division->refresh();

            // Keep a fresh instance on the component if it uses it
            $this->divisionForm->division['status'] = $responseData['status'];

            if (property_exists($this, 'statusLabel')) {
                $this->statusLabel = DivisionStatus::tryFrom($responseData['status'])?->label() ?? __('forms.unknown');
            }

            if (property_exists($this, 'statusStyle')) {
                $this->statusStyle = DivisionStatus::tryFrom($responseData['status'])?->cssClass() ?? 'status-alert-default';
            }
        } catch (Exception $err) {
            Log::channel('db_errors')->error(static::class . ':activateDivision:', ['message' => $err->getMessage()]);

            session()->flash('error', __('divisions.errors.activate'));
        }
    }

    /**
     * Set 'INACTIVE' action status for specified division
     *
     * @param  int  $divisionId
     * @return void
     * @throws Exception|EHealthResponseException
     */
    public function deactivate(int $divisionId): void
    {
        $division = $this->getDivision($divisionId);

        if (!$division) {
            return;
        }

        if (Auth::user()->cannot('deactivate', $division)) {
            session()->flash('error', __('divisions.policy.deny.deactivate'));

            return;
        }

        if ($validationError = $this->getDeactivationValidationError($division)) {
            session()->flash('error', $validationError);

            return;
        }

        try {
            $response = EHealth::division()->deactivate($division->uuid);

            session()->flash('success', __('divisions.request.deactivated'));
        } catch (EHealthResponseException $err) {
            $err->handle(static::class . ':deactivateDivision:', __('divisions.errors.deactivate'));

            return;
        }

        $responseData = $response->getData();

        try {
            Repository::division()->setAction($division, $responseData['status']);

            // Refresh model and sync local state
            $division->refresh();

            // Keep a fresh instance on the component if it uses it
            $this->divisionForm->division['status'] = $responseData['status'];

            if (property_exists($this, 'statusLabel')) {
                $this->statusLabel = DivisionStatus::tryFrom($responseData['status'])?->label() ?? __('forms.unknown');
            }

            if (property_exists($this, 'statusStyle')) {
                $this->statusStyle = DivisionStatus::tryFrom($responseData['status'])?->cssClass() ?? 'status-alert-default';
            }
        } catch (Exception $err) {
            Log::channel('db_errors')->error(static::class . ':deactivateDivision:', ['message' => $err->getMessage()]);

            session()->flash('error', __('divisions.errors.deactivate'));
        }
    }

    /**
     * Validate local dependencies before requesting division deactivation from eHealth.
     */
    protected function getDeactivationValidationError(Division $division): ?string
    {
        // Check that there are no active healthcare services related to this division
        if ($division->healthcareServices()
            ->where('status', HealthcareServiceStatus::ACTIVE)
            ->exists()) {
            return __('divisions.errors.deactivate_active_healthcare_services');
        }

        // Check that there are no active employees related to this division
        if ($division->employees()
            ->where('status', Status::APPROVED)
            ->exists()) {
            return __('divisions.errors.deactivate_active_employees');
        }

        // Check that there are no active records in equipment table related to this division
        if ($division->equipments()
            ->where('status', EquipmentStatus::ACTIVE)
            ->exists()) {
            return __('divisions.errors.deactivate_active_equipments');
        }
    }

    /**
     * Delete the record from DB for specified division
     * NOTE: only for divsions with DRAFT status!
     *
     * @param  int  $divisionId
     * @return void
     * @throws Exception|Throwable
     */
    public function delete(int $divisionId): void
    {
        $division = $this->getDivision($divisionId);

        if (!$division) {
            return;
        }

        if (Auth::user()->cannot('delete', $division)) {
            session()->flash('error', __('divisions.policy.deny.delete'));

            return;
        }

        try {
            $division->delete();

            $this->redirect(route('division.index', [legalEntity()]), navigate: true);

            session()->flash('success', __('divisions.draft.delete_success'));
        } catch (Throwable $err) {
            Log::channel('db_errors')->error(static::class . ':deleteDraft:', ['message' => $err->getMessage()]);

            session()->flash('error', __('divisions.draft.errors.delete'));

            return;
        }
    }

    /**
     * Retrieves a Division model by its primary key.
     *
     * @param  int  $id
     * @return Division|null
     */
    protected function getDivision(int $id): ?Division
    {
        $division = Division::find($id);

        if (!$division) {
            Log::channel('db_errors')->error(static::class . ':getDivision:', ['message' => "Cannot find model with id=$id"]);

            session()->flash('error', __('errors.ehealth.messages.request_error'));

            return null;
        }

        return $division;
    }
}
