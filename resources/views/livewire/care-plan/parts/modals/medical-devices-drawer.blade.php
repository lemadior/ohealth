@php
    $dictionaries = $dictionaries ?? [];
@endphp

<x-dialog-drawer
    x-model="showMedicalDeviceDrawer"
    noTeleport="true"
    topClass="top-[57px]"
    zIndex="40"
    customWidth="w-full sm:w-4/5"
    hasClose="true"
    onCloseClick="showMedicalDeviceDrawer = false"
>

    <h3 class="modal-header" id="medical-devices-drawer-label">
            {{ __('care-plan.new_medical_device_prescription') }}
        </h3>

        {{-- Content --}}
        <form>
            {{-- Program Selection Section --}}
            <fieldset class="fieldset">
                <legend class="legend">
                    {{ __('care-plan.program_selection') }}
                </legend>

                <div class="form-row-3">
                    <div class="form-group group">
                        <label for="medical_device_program" class="label">
                            {{ __('care-plan.program') }}*
                        </label>
                        <select id="medical_device_program"
                                name="medical_device_program"
                                class="input-select peer"
                                wire:model="selectedProgram"
                        >
                            <option value="">{{ __('care-plan.medical_guarantees_program') }}</option>
                            @foreach(($dictionaries['medical_programs'] ?? []) as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <div class="mt-6 flex justify-start gap-3">
                <button type="button"
                        class="button-minor"
                        aria-controls="medical-devices-drawer-right"
                        @click="showMedicalDeviceDrawer = false"
                >
                    {{ __('forms.cancel') }}
                </button>

                <button type="button"
                        class="button-primary"
                        @click="showMedicalDeviceDrawer = false; showMedicalDeviceSearchDrawer = true"
                >
                    {{ __('forms.continue') }}
                </button>
            </div>
        </form>
</x-dialog-drawer>

