@php
    $availableConditions = $availableConditions ?? [];
    $availableObservations = $availableObservations ?? [];
    $availableReports = $availableReports ?? [];
    $linkedGrounds = $linkedGrounds ?? [];
    $selectedProduct = $selectedProduct ?? null;
    $activityForm = $activityForm ?? [];
@endphp

{{-- Services Drawer --}}
<x-dialog-drawer
    x-model="showServiceDrawer"
    noTeleport="true"
    topClass="top-[57px]"
    zIndex="40"
    customWidth="w-full sm:w-4/5"
    hasClose="true"
    onCloseClick="showServiceDrawer = false"
>

    {{-- Header Area --}}
    <div class="mb-6">
        <div class="text-sm text-gray-500 mb-1">
            {{ $carePlan->person->fullName }} - {{ __('care-plan.care_plan_no') }}{{ $carePlan->requisition ?? $carePlan->id }}
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white" id="services-drawer-label">
            {{ __('care-plan.new_service_prescription') }}
        </h2>
    </div>

    {{-- Content --}}
    <form wire:submit.prevent="saveActivity" class="space-y-6 flex-1 flex flex-col justify-between">
        <div class="space-y-6">
            {{-- Section 1: Main Data --}}
            <fieldset class="fieldset">
                <legend class="legend">{{ __('care-plan.main_data') }}</legend>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    {{-- Service Selector --}}
                    <div class="form-group group">
                        <label for="service" class="label">{{ __('care-plan.service') }}*</label>
                        <div class="relative">
                            <button type="button"
                                    class="input-select peer pr-12 w-full text-left {{ !empty($selectedProduct) ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500' }}"
                                    aria-controls="service-search-drawer-right"
                                    @click="showServiceSearchDrawer = true"
                            >
                                {{ !empty($selectedProduct) ? (($selectedProduct['code'] ?? '') . ' - ' . ($selectedProduct['name'] ?? '')) : __('care-plan.select_service') }}
                            </button>
                            <button type="button" @click="showServiceSearchDrawer = true" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                        @error('activityForm.product_reference') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    {{-- Program Selector --}}
                    <div class="form-group group">
                        <label for="program" class="label">{{ __('care-plan.program') }}</label>
                        <select id="program" name="program" class="input-select peer w-full">
                            <option selected value="">{{ __('care-plan.state_financial_guarantees') }}</option>
                        </select>
                    </div>

                    {{-- Quantity --}}
                    <div class="form-group group">
                        <label for="quantity" class="label">{{ __('care-plan.quantity') }}</label>
                        <div class="flex gap-2">
                            <input type="number" id="quantity" class="input peer w-full" wire:model="activityForm.quantity">
                            <select class="input-select peer w-24" wire:model="activityForm.quantity_system">
                                <option value="units">{{ __('care-plan.units') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Start Date & Time --}}
                    <div class="form-group group">
                        <label class="label">{{ __('care-plan.start_date') }}:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    @icon('calendar-week', 'w-4 h-4 text-gray-500')
                                </div>
                                <input type="text"
                                       class="input peer ps-10"
                                       placeholder="02.04.2025"
                                       datepicker-autohide
                                       datepicker-format="dd.mm.yyyy"
                                       datepicker-button="false"
                                       wire:model.live="activityForm.scheduled_period_start"
                                />
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <input type="text" class="input timepicker-uk ps-10" placeholder="02:30 PM" />
                            </div>
                        </div>
                    </div>

                    {{-- Quantity per time --}}
                    <div class="form-group group">
                        <label for="quantity_per_time" class="label">{{ __('care-plan.quantity_per_time') }}</label>
                        <div class="flex gap-2">
                            <input type="number" id="quantity_per_time" name="quantity_per_time" class="input peer w-full" value="1">
                            <select class="input-select peer w-24">
                                <option selected value="units">{{ __('care-plan.units') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- End Date & Time --}}
                    <div class="form-group group">
                        <label class="label">{{ __('care-plan.end_date') }}:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    @icon('calendar-week', 'w-4 h-4 text-gray-500')
                                </div>
                                <input type="text"
                                       class="input peer ps-10"
                                       placeholder="02.08.2025"
                                       datepicker-autohide
                                       datepicker-format="dd.mm.yyyy"
                                       datepicker-button="false"
                                       wire:model.live="activityForm.scheduled_period_end"
                                />
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <input type="text" class="input timepicker-uk ps-10" placeholder="02:30 PM" />
                            </div>
                        </div>
                    </div>

                    {{-- Number of times --}}
                    <div class="form-group group">
                        <label for="number_of_times" class="label">{{ __('care-plan.number_of_times') }}</label>
                        <div class="flex gap-2">
                            <input type="number" id="number_of_times" name="number_of_times" class="input peer w-full" value="1">
                            <select class="input-select peer w-28">
                                <option selected value="per_day">{{ __('care-plan.per_day') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Duration --}}
                    <div class="form-group group">
                        <label for="duration" class="label">{{ __('care-plan.duration') }}</label>
                        <div class="flex gap-2">
                            <input type="number" id="duration" name="duration" class="input peer w-full" value="10">
                            <select class="input-select peer w-24">
                                <option selected value="days">{{ __('care-plan.days') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- Section 2: Grounds for Prescription --}}
            <fieldset class="fieldset" x-data="{ selectedGround: '' }">
                <legend class="legend">{{ __('care-plan.grounds_for_prescription') }}</legend>

                <div class="mb-6 max-w-xl">
                    <select x-model="selectedGround" class="input-select peer w-full">
                        <option value="">{{ __('care-plan.select_icd10_code') }}</option>
                        @if(!empty($availableConditions))
                            <optgroup label="{{ __('care-plan.diagnoses_conditions') }}">
                                @foreach($availableConditions as $cond)
                                    <option value="Condition|{{ $cond['uuid'] }}">{{ $cond['name'] }} ({{ __('care-plan.from') }} {{ $cond['date'] }})</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if(!empty($availableReports))
                            <optgroup label="{{ __('care-plan.diagnostic_reports') }}">
                                @foreach($availableReports as $report)
                                    <option value="DiagnosticReport|{{ $report['uuid'] }}">{{ $report['name'] }} ({{ __('care-plan.from') }} {{ $report['date'] }})</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if(!empty($availableObservations))
                            <optgroup label="{{ __('care-plan.observations') }}">
                                @foreach($availableObservations as $obs)
                                    <option value="Observation|{{ $obs['uuid'] }}">{{ $obs['name'] }} ({{ __('care-plan.from') }} {{ $obs['date'] }})</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </div>

                <div class="mb-4">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white mb-4">
                        {{ __('care-plan.justification_of_grounds') }}
                    </h4>

                    <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="thead-input">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('care-plan.date') }}</th>
                                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('care-plan.name') }}</th>
                                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('care-plan.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($linkedGrounds as $ground)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $ground['date'] }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                                            {{ $ground['name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" wire:click="removeLinkedGround('{{ $ground['uuid'] }}')" class="text-black dark:text-white hover:opacity-70 transition-opacity inline-block cursor-pointer">
                                                @icon('delete', 'w-5 h-5')
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-400 italic">
                                            {{ __('care-plan.no_justification_added') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button type="button" 
                                @click="showMedicalRecordsSearchDrawer = true" 
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm flex items-center gap-1 transition-colors cursor-pointer"
                        >
                            + {{ __('care-plan.add_medical_record') }}
                        </button>
                    </div>
                </div>
            </fieldset>

            {{-- Section 3: Additional Information --}}
            <fieldset class="fieldset">
                <legend class="legend">{{ __('care-plan.additional_info') }}</legend>

                <div class="form-row-3 mb-4">
                    <label for="expected_result" class="label">{{ __('care-plan.expected_result') }}</label>
                    <select id="expected_result" name="expected_result" class="input-select peer w-full">
                        <option selected value="">{{ __('care-plan.select_result') }}</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="description" class="label">{{ __('care-plan.extended_description') }}</label>
                    <textarea id="description"
                              class="input peer w-full"
                              rows="4"
                              placeholder="{{ __('care-plan.description') }}"
                              wire:model="activityForm.description"
                    ></textarea>
                </div>
            </fieldset>
        </div>

        {{-- Footer Buttons --}}
        <div class="mt-8 flex justify-start gap-3 pt-6 border-t border-gray-100">
            <button type="button"
                    class="button-minor"
                    @click="showServiceDrawer = false"
            >
                {{ __('forms.cancel') }}
            </button>

            <button type="submit"
                    class="button-primary"
            >
                {{ __('forms.save') }}
            </button>
        </div>
    </form>
</x-dialog-drawer>
