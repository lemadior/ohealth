<x-layouts.patient
    :prepersonId="$preperson->id"
    :patientFullName="$preperson->fullName"
    :title="'ID ' . $preperson->externalId"
    :activeTab="'patient-data'"
>
    @include('livewire.preperson.parts.preperson-data')
</x-layouts.patient>
