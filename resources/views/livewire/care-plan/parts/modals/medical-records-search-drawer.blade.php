@php
    $availableConditions = $availableConditions ?? [];
    $availableObservations = $availableObservations ?? [];
    $availableReports = $availableReports ?? [];
@endphp

<x-dialog-drawer
    x-model="showMedicalRecordsSearchDrawer"
    noTeleport="true"
    topClass="top-[57px]"
    zIndex="44"
    customWidth="w-full sm:w-[calc(80%-15%)]"
    overlayWidth="80%"
    hasClose="true"
    onCloseClick="showMedicalRecordsSearchDrawer = false"
    title="{{ __('care-plan.search_medical_records') }}"
>
    <div x-data="{
        searchQuery: '',
        selectedType: 'Condition',
        records: [
            @foreach(($availableConditions ?? []) as $item)
            {
                uuid: '{{ $item['uuid'] }}',
                type: 'Condition',
                typeName: '{{ __('care-plan.condition_diagnosis') }}',
                name: '{{ addslashes($item['name']) }}',
                date: '{{ $item['date'] }}'
            },
            @endforeach
            @foreach(($availableObservations ?? []) as $item)
            {
                uuid: '{{ $item['uuid'] }}',
                type: 'Observation',
                typeName: '{{ __('care-plan.observation') }}',
                name: '{{ addslashes($item['name']) }}',
                date: '{{ $item['date'] }}'
            },
            @endforeach
            @foreach(($availableReports ?? []) as $item)
            {
                uuid: '{{ $item['uuid'] }}',
                type: 'DiagnosticReport',
                typeName: '{{ __('care-plan.diagnostic_report') }}',
                name: '{{ addslashes($item['name']) }}',
                date: '{{ $item['date'] }}'
            },
            @endforeach
        ],
        get filteredRecords() {
            return this.records.filter(r => {
                const matchesType = !this.selectedType || r.type === this.selectedType;
                const matchesQuery = !this.searchQuery || r.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                return matchesType && matchesQuery;
            });
        }
    }" class="flex flex-col h-full w-full">

    {{-- Search Fields Box --}}
    <fieldset class="fieldset bg-white dark:bg-gray-800 !rounded-xl !border-gray-100 dark:!border-gray-700 !max-w-full !p-6 !mb-6 shadow-sm">
        <legend class="legend">{{ __('care-plan.search_medical_records') }}</legend>

        <div class="space-y-6">
            {{-- Search icon + text header --}}
            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300 font-medium">
                @icon('search-outline', 'w-5 h-5 text-gray-400')
                <span>{{ __('care-plan.search') }}</span>
            </div>

            {{-- Filters row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Type select --}}
                <div class="form-group group">
                    <label class="label">{{ __('care-plan.medical_record_type') }}</label>
                    <select x-model="selectedType" class="input-select peer w-full">
                        <option value="">{{ __('care-plan.all_types') }}</option>
                        <option value="Condition">{{ __('care-plan.diagnoses_conditions') }}</option>
                        <option value="Observation">{{ __('care-plan.observations') }}</option>
                        <option value="DiagnosticReport">{{ __('care-plan.diagnostic_reports') }}</option>
                    </select>
                </div>

                {{-- Episode placeholder --}}
                <div class="form-group group">
                    <label class="label">{{ __('care-plan.episode') }}</label>
                    <select class="input-select peer w-full">
                        <option value="">{{ __('care-plan.select_episode_placeholder') }}</option>
                        <option value="1">{{ __('care-plan.health_maintenance_prevention_active_from') }} 8.05.2025</option>
                    </select>
                </div>
            </div>
        </div>
    </fieldset>

    {{-- Results Table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 mb-6">
        <table class="w-full text-sm text-left">
            <thead class="thead-input">
                <tr>
                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('care-plan.date') }}</th>
                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('care-plan.type') }}</th>
                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('care-plan.name') }}</th>
                    <th scope="col" class="px-4 py-3 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('care-plan.action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                <template x-for="item in filteredRecords" :key="item.uuid">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-4 text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="item.date"></td>
                        <td class="px-4 py-4 text-gray-500 dark:text-gray-400" x-text="item.typeName"></td>
                        <td class="px-4 py-4 font-medium text-gray-900 dark:text-white" x-text="item.name"></td>
                        <td class="px-4 py-4 text-right">
                            <button type="button" 
                                    @click="$wire.addLinkedGround(item.type, item.uuid); showMedicalRecordsSearchDrawer = false;" 
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium inline-flex items-center justify-center p-1 rounded-full border border-blue-600 hover:bg-blue-50 transition-colors"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <template x-if="filteredRecords.length === 0">
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">
                            {{ __('care-plan.nothing_found_by_filters') }}
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
        <button type="button"
                class="button-minor"
                @click="showMedicalRecordsSearchDrawer = false"
        >
            {{ __('forms.cancel') }}
        </button>
    </div>
    </div>
</x-dialog-drawer>
