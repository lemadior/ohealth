@php
    $searchQuery = $searchQuery ?? '';
    $searchResults = $searchResults ?? [];
@endphp

<x-dialog-drawer
    x-model="showMedicalDeviceSearchDrawer"
    noTeleport="true"
    topClass="top-[57px]"
    zIndex="42"
    customWidth="w-full sm:w-[calc(80%-15%)]"
    overlayWidth="80%"
    hasClose="true"
    onCloseClick="showMedicalDeviceSearchDrawer = false"
    title="{{ __('care-plan.medical_device_search') }}"
>
    <div x-data="{ showFilter: false }" class="flex flex-col h-full w-full">

    {{-- Search Input --}}
    <div class="mb-4">
        <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                @icon('search-outline', 'w-5 h-5 text-gray-500')
            </div>
            <input type="text"
                   class="input peer ps-10 w-full"
                   placeholder="{{ __('care-plan.test_strips') }}"
            />
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <button type="button" class="button-primary flex items-center gap-2">
            @icon('search', 'w-4 h-4')
            <span>{{ __('forms.search') }}</span>
        </button>
        <button type="button" class="button-primary-outline-red">
            {{ __('forms.reset_all_filters') }}
        </button>
        <button type="button"
                class="button-minor flex items-center gap-2"
                @click="showFilter = !showFilter"
        >
            @icon('adjustments', 'w-4 h-4')
            <span>{{ __('forms.additional_search_parameters') }}</span>
        </button>
    </div>

    {{-- Filters --}}
    <div x-show="showFilter" x-cloak x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="form-group group">
            <label class="label">
                {{ __('care-plan.medical_device_type') }}
            </label>
            <select class="input-select peer w-full">
                <option selected value="">{{ __('care-plan.glucose_test_reagent') }}</option>
            </select>
        </div>
        <div class="form-group group">
            <label class="label">
                {{ __('care-plan.medical_device_model_number') }}
            </label>
            <select class="input-select peer w-full">
                <option selected value="">{{ __('care-plan.yes') }}</option>
            </select>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-sm text-left">
            <thead class="thead-input">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('care-plan.name') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('care-plan.type') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('care-plan.packaging') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('care-plan.program_participants') }}</th>
                    <th scope="col" class="px-4 py-3 font-medium">{{ __('care-plan.action') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-900 dark:text-white">{{ __('care-plan.glucose_test_reagent') }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ __('care-plan.medical_device_for_glucose') }}
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ __('care-plan.box_50_pieces') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs">DM <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">?</span></span>
                            <span class="text-xs">RightTest ELSA <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">?</span></span>
                            <span class="text-xs">RightTest Ultra <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">?</span></span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <button type="button" class="text-blue-500 hover:text-blue-700" @click="showMedicalDeviceFormDrawer = true">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <button type="button"
                class="button-minor"
                @click="showMedicalDeviceSearchDrawer = false"
        >
            {{ __('forms.cancel') }}
        </button>
    </div>
    </div>
</x-dialog-drawer>
