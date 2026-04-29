<?php

declare(strict_types=1);

namespace App\Livewire\Encounter;

use App\Classes\Cipher\Api\CipherRequest;
use App\Classes\Cipher\Exceptions\CipherApiException;
use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Encounter;
use App\Repositories\MedicalEvents\Repository;
use App\Services\MedicalEvents\Mappers\ConditionMapper;
use App\Services\MedicalEvents\Mappers\EncounterMapper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use JsonException;
use Livewire\Attributes\Locked;
use Throwable;

class EncounterEdit extends EncounterComponent
{
    #[Locked]
    public int $encounterId;

    public function mount(LegalEntity $legalEntity, int $personId, int $encounterId): void
    {
        $this->initializeComponent($personId);
        $this->encounterId = $encounterId;

        $encounter = Encounter::withRelationships()->whereId($encounterId)->firstOrFail()->toArray();

        $this->form->encounter = app(EncounterMapper::class)->fromFhir($encounter);

        $episodeUuid = data_get($encounter, 'episode.identifier.value', '');

        $this->episodeType = 'existing';
        $this->form->episode['id'] = $episodeUuid;

        $conditions = Repository::condition()->getByUuids(
            collect(data_get($encounter, 'diagnoses', []))
                ->pluck('condition.identifier.value')
                ->filter()
                ->values()
                ->toArray()
        );

        $detailsMap = Repository::condition()->getDetailsMapForEvidences($conditions);

        $this->form->conditions = collect($conditions)
            ->map(fn (array $condition) => app(ConditionMapper::class)->fromFhir($condition, $detailsMap))
            ->toArray();

        //        $this->form->immunizations = Repository::immunization()->get($this->encounterId);
        //        $this->form->immunizations = Repository::immunization()->formatForView($this->form->immunizations);
        //
        //        $this->form->diagnosticReports = Repository::diagnosticReport()->get($this->encounterId);
        //        $this->form->diagnosticReports = Repository::diagnosticReport()->formatForView($this->form->diagnosticReports);
        //
        //        $this->form->observations = Repository::observation()->get($this->encounterId);
        //        $this->form->observations = Repository::observation()->formatForView($this->form->observations);
        //
        //        $this->form->procedures = Repository::procedure()->get($this->encounterId);
        //        $this->form->procedures = Repository::procedure()->formatForView($this->form->procedures);
        //
        //        $this->form->clinicalImpressions = Repository::clinicalImpression()->get($this->encounterId);
    }

    /**
     * Validate and update data.
     *
     * @return array|null
     */
    public function save(): ?array
    {
        try {
            $validated = $this->form->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return null;
        }

        // format to fhir format for local saving(updating)
        $encounter = Encounter::withRelationships()->whereId($this->encounterId)->firstOrFail();
        $uuids = [
            'encounter' => $encounter->uuid,
            'visit' => data_get($encounter->toArray(), 'visit.identifier.value'),
            'employee' => Auth::user()->getEncounterWriterEmployee()->uuid,
            'episode' => $validated['episode']['id']
        ];
        $fhirConditions = collect($validated['conditions'] ?? [])
            ->map(fn (array $condition) => app(ConditionMapper::class)->toFhir($condition, $uuids))
            ->values()
            ->toArray();
        $fhirEncounter = app(EncounterMapper::class)->toFhir(
            $validated['encounter'],
            $fhirConditions,
            $uuids
        );

        // map id to uuid for using sync method
        $conditionsSyncData = collect($fhirConditions)->map(
            fn (array $item) => collect($item)->put('uuid', $item['id'])->forget(['id'])->all()
        )->toArray();
        $encounterSyncData = collect($fhirEncounter)
            ->put('uuid', $fhirEncounter['id'])
            ->forget(['id'])
            ->all();

        try {
            Repository::encounter()->sync($this->personId, [Arr::toSnakeCase($encounterSyncData)]);
            Repository::condition()->sync($this->personId, Arr::toSnakeCase($conditionsSyncData));
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to sync encounter package data');
            Session::flash('error', __('messages.database_error'));

            return null;
        }

        Session::flash('success', __('patients.messages.encounter_updated'));

        return [
            'encounter' => $fhirEncounter,
            'conditions' => $fhirConditions
        ];
    }

    /**
     * Submit encrypted data about person encounter.
     *
     * @return void
     */
    public function sign(): void
    {
        if (Auth::user()->cannot('create', Encounter::class)) {
            Session::flash('error', __('patients.policy.create_encounter'));

            return;
        }

        try {
            $validated = $this->form->validate($this->form->signingRules());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $formattedData = $this->save();
        $formattedData = Arr::toSnakeCase($formattedData);

        try {
            $signedContent = new CipherRequest()->signData(
                $formattedData,
                $validated['knedp'],
                $validated['keyContainerUpload'],
                $validated['password'],
                Auth::user()->party->taxId
            );
        } catch (ConnectionException|CipherApiException|JsonException $exception) {
            $this->handleCipherExceptions($exception, 'Error when signing data with Cipher');

            return;
        }

        try {
            $resp = EHealth::encounter()->submit($this->patientUuid, [
                'visit' => [
                    'id' => data_get($formattedData, 'encounter.visit.identifier.value'),
                    'period' => data_get($formattedData, 'encounter.period')
                ],
                'signed_data' => $signedContent->getBase64Data()
            ]);

            logger()->debug('Job ID to further debug', $resp->getData());
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error while submitting encounter');

            return;
        }

        $this->redirectRoute('persons.index', [legalEntity()], navigate: true);
    }
}
