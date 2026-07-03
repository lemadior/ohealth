<?php

declare(strict_types=1);

namespace App\Livewire\Preperson;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Preperson\Status;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Livewire\Preperson\Forms\PrepersonForm as Form;
use App\Models\Preperson;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class PrepersonCreate extends Component
{
    use FormTrait;

    public Form $form;

    /**
     * Whether the modal proposing alternative patient identification by observations is open.
     *
     * @var bool
     */
    public bool $showAlternativeIdentificationModal = false;

    /**
     * MPI identifier of the just-registered preperson, used to build the encounter link.
     *
     * @var string|null
     */
    public ?string $createdPrepersonId = null;

    public array $dictionaryNames = [
        'GENDER',
        'PHONE_TYPE'
    ];

    public function mount(): void
    {
        $this->getDictionary();
    }

    /**
     * Validate and store an unidentified patient (preperson) draft locally.
     *
     * @return void
     */
    public function createLocally(): void
    {
        if (Auth::user()->cannot('create', Preperson::class)) {
            Session::flash('error', __('patients.policy.create'));

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForCreate());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $personData = $validated['person'];
        // note is the eHealth-facing text; reasonContext keeps the raw fields so the draft can be re-edited later
        $personData['note'] = $this->form->buildNote();
        $personData['reasonContext'] = $validated['reasonContext'];
        $personData['status'] = Status::DRAFT->value;

        if (!empty($personData['birthDate'])) {
            $personData['birthDate'] = convertToYmd($personData['birthDate']);
        }

        try {
            DB::transaction(static function () use ($personData): void {
                $preperson = Preperson::create(Arr::toSnakeCase($personData));
                // external_id follows the mask MIS.NMP.id, so it is assigned only after the insert produces a primary key
                $preperson->externalId = $preperson->buildExternalId();
                $preperson->save();
            });
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Failed to store preperson');

            return;
        }

        Session::flash('success', __('patients.messages.preperson_draft_created'));
        $this->redirectRoute('persons.index', [legalEntity()], navigate: true);
    }

    /**
     * Validate, register the unidentified patient in eHealth and persist it locally.
     *
     * @return void
     */
    public function create(): void
    {
        if (Auth::user()->cannot('create', Preperson::class)) {
            Session::flash('error', __('patients.policy.create'));

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForCreate());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $personData = $validated['person'];
        $personData['note'] = $this->form->buildNote();

        if (!empty($personData['birthDate'])) {
            $personData['birthDate'] = convertToYmd($personData['birthDate']);
        }

        // Insert locally first to obtain a primary key for external_id — reserving a key without inserting
        // Reason_context is stored only here, never sent to eHealth.
        $record = Arr::toSnakeCase($personData);
        $record['reason_context'] = Arr::toSnakeCase($validated['reasonContext']);

        try {
            $preperson = DB::transaction(static function () use ($record): Preperson {
                $preperson = Preperson::create($record);
                $preperson->externalId = $preperson->buildExternalId();
                $preperson->save();

                return $preperson;
            });
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Failed to store preperson');

            return;
        }

        // Built from $personData (not $record) so reason_context never leaks into the eHealth request
        $payload = Arr::toSnakeCase($personData);
        $payload['external_id'] = $preperson->externalId;

        try {
            $response = EHealth::preperson()->create($payload);
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when creating a preperson');

            return;
        }

        if ($response->successful()) {
            try {
                // forceFill bypasses mass-assignment guards so identity fields (uuid) set from the trusted eHealth response
                $preperson->forceFill($response->validate())->save();
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update preperson from eHealth response');

                return;
            }

            // Offer to start an "alternative identification" encounter for the freshly registered preperson
            $this->createdPrepersonId = $preperson->uuid;
            $this->showAlternativeIdentificationModal = true;
        }
    }

    public function render(): View
    {
        return view('livewire.preperson.preperson-create');
    }
}
