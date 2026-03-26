<x-layouts.patient :id="$id" :patientFullName="$patientFullName">
    <x-slot name="headerActions">
        @can('create', \App\Models\MedicalEvents\Sql\Encounter::class)
            <a href="{{ route('encounter.create', [legalEntity(), 'patientId' => $id]) }}"
               class="flex items-center gap-2 button-primary px-5 py-2 text-sm shadow-sm"
            >
                @icon('plus', 'w-4 h-4')
                {{ __('patients.start_interacting') }}
            </a>
        @endcan

        <button type="button"
                class="button-primary-outline whitespace-nowrap px-5 py-2 text-sm"
        >
            {{ __('patients.data_access') }}
        </button>

        <button wire:click.prevent="syncEpisodes"
                type="button"
                class="button-sync flex items-center gap-2 whitespace-nowrap px-5 py-2 text-sm shadow-sm"
        >
            @icon('refresh', 'w-4 h-4')
            {{ __('patients.sync_ehealth_data') }}
        </button>
    </x-slot>

    <div class="breadcrumb-form p-4 shift-content">

        <div x-data="{ activeTab: 'summary' }"
             class="w-full flex items-center justify-between overflow-x-auto bg-gray-100 dark:bg-gray-800/50 p-1 px-2 xl:p-1.5 xl:px-3 rounded-xl mb-10 text-[13px] xl:text-sm border border-transparent dark:border-gray-700/50"
        >
            <a href="{{ route('persons.patient-data', [legalEntity(), 'id' => $id]) }}"
               :class="activeTab === 'patient-data' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
               @click.prevent="activeTab = 'patient-data'; window.location.href = this.href"
               class="summary-tab"
            >
                {{ __('patients.patient_data') }}
            </a>

            <button type="button"
                    @click.prevent="activeTab = 'summary'"
                    :class="activeTab === 'summary' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="summary-tab"
            >
                {{ __('patients.summary') }}
            </button>

            <button type="button"
                    wire:click.once="getDiagnoses"
                    @click.prevent="activeTab = 'diagnoses'"
                    :class="activeTab === 'diagnoses' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="summary-tab"
            >
                {{ __('patients.diagnoses') }}
            </button>

            <button type="button"
                    wire:click.once="getObservations"
                    @click.prevent="activeTab = 'observations'"
                    :class="activeTab === 'observations' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="summary-tab"
            >
                {{ __('patients.observation') }}
            </button>

            <button type="button"
                    @click.prevent="activeTab = 'vaccinations'"
                    :class="activeTab === 'vaccinations' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="summary-tab"
            >
                {{ __('patients.vaccinations') }}
            </button>

            <button type="button"
                    @click.prevent="activeTab = 'procedures'"
                    :class="activeTab === 'procedures' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="summary-tab"
            >
                {{ __('patients.procedures') }}
            </button>

            <button type="button"
                    @click.prevent="activeTab = 'prescriptions'"
                    :class="activeTab === 'prescriptions' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="summary-tab"
            >
                {{ __('patients.prescriptions') }}
            </button>

            <button type="button"
                    @click.prevent="activeTab = 'treatment_plans'"
                    :class="activeTab === 'treatment_plans' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="inline-flex items-center px-2.5 xl:px-3 py-1.5 font-medium rounded-lg whitespace-nowrap transition-colors"
            >
                {{ __('patients.treatment_plans') }}
            </button>

            <button type="button"
                    @click.prevent="activeTab = 'diagnostic_reports'"
                    :class="activeTab === 'diagnostic_reports' ? 'bg-blue-600 text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="inline-flex items-center px-2.5 xl:px-3 py-1.5 font-medium rounded-lg whitespace-nowrap transition-colors"
            >
                {{ __('patients.diagnostic_reports') }}
            </button>

            <button type="button" class="inline-flex items-center px-2 py-1.5 text-gray-900 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors ml-1">
                <span class="block px-2 flex items-center justify-center space-x-1">
                    <span class="w-1.5 h-1.5 bg-gray-700 dark:bg-gray-400 rounded-full"></span>
                    <span class="w-1.5 h-1.5 bg-gray-700 dark:bg-gray-400 rounded-full"></span>
                    <span class="w-1.5 h-1.5 bg-gray-700 dark:bg-gray-400 rounded-full"></span>
                </span>
            </button>
        </div>

        @php
            $navItems = [
                ['id' => 'episodes', 'action' => 'getEpisodes', 'label' => __('patients.episodes'), 'icon' => 'book'],
                ['id' => 'interaction', 'action' => '', 'label' => __('patients.interaction'), 'icon' => 'users'],
                ['id' => 'clinical_impressions', 'action' => '', 'label' => __('patients.clinical_impressions'), 'icon' => 'check'],
                ['id' => 'vaccinations', 'action' => '', 'label' => __('patients.vaccinations'), 'icon' => 'shield'],
                ['id' => 'observation', 'action' => 'getObservations', 'label' => __('patients.observation'), 'icon' => 'heart'],
                ['id' => 'diagnoses', 'action' => 'getDiagnoses', 'label' => __('patients.diagnoses'), 'icon' => 'file'],
                ['id' => 'condition', 'action' => '', 'label' => __('patients.condition'), 'icon' => 'file-minus'],
                ['id' => 'diagnostic_reports', 'action' => '', 'label' => __('patients.diagnostic_reports'), 'icon' => 'activity'],
                ['id' => 'allergies', 'action' => '', 'label' => __('patients.allergies'), 'icon' => 'alert'],
                ['id' => 'risk_assessments', 'action' => '', 'label' => __('patients.risk_assessments'), 'icon' => 'alert-octagon'],
                ['id' => 'devices', 'action' => '', 'label' => __('patients.devices'), 'icon' => 'equipment'],
                ['id' => 'medicines', 'action' => '', 'label' => __('patients.medicines'), 'icon' => 'pill-outline'],
            ];
        @endphp

        <div x-data="{ activeSection: '' }" class="flex flex-col lg:flex-row gap-8 lg:gap-12">


            <div class="flex-1 space-y-4">
                @foreach($navItems as $item)
                    <div id="block-{{ $item['id'] }}"
                         class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl transition-all scroll-mt-8"
                         :class="activeSection === '{{ $item['id'] }}' ? 'border-gray-200 dark:border-gray-600 shadow-md' : 'border-gray-100 hover:shadow-md hover:bg-gray-50 dark:hover:bg-gray-700/80'"
                    >
                        <button @if($item['action']) wire:click.once="{{ $item['action'] }}" @endif
                        @click="activeSection = activeSection === '{{ $item['id'] }}' ? '' : '{{ $item['id'] }}'"
                                type="button"
                                class="w-full flex items-center justify-between p-5 focus:outline-none"
                        >
                            <div class="flex items-center gap-4 text-gray-900 dark:text-gray-100 font-medium text-[15px]">
                                <span class="w-6 h-6 flex items-center justify-center shrink-0 text-gray-900 dark:text-gray-100">
                                    @icon($item['icon'], 'w-6 h-6')
                                </span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </div>

                            <div class="flex items-center gap-4 text-sm font-medium">
                                <span x-show="activeSection === '{{ $item['id'] }}'"
                                      class="hidden sm:flex text-blue-600 dark:text-blue-400 items-center gap-1.5 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                                      @click.stop=""
                                >
                                    @icon('refresh', 'w-4 h-4')
                                    {{ __('patients.sync_ehealth_data') }}
                                </span>
                                <div class="shrink-0 text-gray-400 dark:text-gray-500 transition-transform duration-300"
                                     :class="activeSection === '{{ $item['id'] }}' ? '' : '-rotate-90'"
                                >
                                    @icon('chevron-down', 'w-5 h-5')
                                </div>
                            </div>
                        </button>

                        <div x-show="activeSection === '{{ $item['id'] }}'" style="display: none;" class="px-5 pb-5">

                            @if($item['id'] === 'episodes')
                                <!-- Internal mock card 1 -->
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_name') }}</div>
                                            <div class="record-inner-value text-[16px]">030.2 | Чотириплідна вагітність</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative" x-data="{ openMenu: false }">
                                            <button @click="openMenu = !openMenu"
                                                    @click.away="openMenu = false"
                                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                                            >
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>

                                            <!-- Dropdown Menu -->
                                            <div x-show="openMenu"
                                                 x-transition.opacity.duration.200ms
                                                 class="absolute right-[50%] md:right-0 top-1/2 md:top-[80%] w-56 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-xl z-20 py-2"
                                                 style="display: none;"
                                            >
                                                <button class="w-full text-left px-4 py-2.5 text-[14px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    {{ __('patients.view_details') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex flex-col justify-center">
                                            <div class="flex items-start justify-between gap-2 xl:gap-4 overflow-hidden">
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.date_opened') }}</div>
                                                    <div class="record-inner-value">02.04.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.date_closed') }}</div>
                                                    <div class="record-inner-value">02.04.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.date_updated') }}</div>
                                                    <div class="record-inner-value">02.02.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.doctor') }}</div>
                                                    <div class="record-inner-value">Сидоренко І.В.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0">
                                                <div class="record-inner-label">ID ECO3</div>
                                                <div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_name') }}</div>
                                            <div class="record-inner-value text-[16px]">030.2 | Чотириплідна вагітність</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative" x-data="{ openMenu: false }">
                                            <button @click="openMenu = !openMenu"
                                                    @click.away="openMenu = false"
                                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                                            >
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>

                                            <!-- Dropdown Menu -->
                                            <div x-show="openMenu"
                                                 x-transition.opacity.duration.200ms
                                                 class="absolute right-[50%] md:right-0 top-1/2 md:top-[80%] w-64 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-xl z-20 py-2"
                                                 style="display: none;"
                                            >
                                                <button class="w-full text-left px-4 py-2.5 text-[14px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    {{ __('patients.get_data_access') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex flex-col justify-center">
                                            <div class="flex items-start justify-between gap-2 xl:gap-4 overflow-hidden">
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.date_opened') }}</div>
                                                    <div class="record-inner-value">02.04.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.date_closed') }}</div>
                                                    <div class="record-inner-value">02.04.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.date_updated') }}</div>
                                                    <div class="record-inner-value">02.02.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.doctor') }}</div>
                                                    <div class="record-inner-value">Сидоренко І.В.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0">
                                                <div class="record-inner-label">ID ECO3</div>
                                                <div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'interaction')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.date') }}</div>
                                            <div class="record-inner-value text-[20px] font-semibold">02.04.2025</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex flex-col justify-center">
                                            <div class="flex items-start justify-between gap-2 xl:gap-4 overflow-hidden">
                                                <div class="flex-1 min-w-0">
                                                    <div class="record-inner-label">{{ __('patients.class') }}</div>
                                                    <div class="record-inner-value truncate">{{ __('patients.inpatient_care') }}</div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="record-inner-label">{{ __('patients.type') }}</div>
                                                    <div class="record-inner-value truncate">{{ __('patients.health_facility_interaction') }}</div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="record-inner-label">{{ __('patients.doctor_speciality') }}</div>
                                                    <div class="record-inner-value truncate">{{ __('patients.surgery') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0">
                                                <div class="record-inner-label">ID ECO3</div>
                                                <div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="record-inner-label">ID Епізоду</div>
                                                <div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'clinical_impressions')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code') }}</div>
                                            <div class="record-inner-value text-[16px]">ЦД. Категорія 3 (студенти)</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.status_completed') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex flex-col justify-center">
                                            <div class="flex items-start justify-between gap-2 xl:gap-4 overflow-hidden">
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.created') }}</div>
                                                    <div class="record-inner-value">02.04.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.start') }}</div>
                                                    <div class="record-inner-value">02.04.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.end') }}</div>
                                                    <div class="record-inner-value">02.02.2025</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.doctor') }}</div>
                                                    <div class="record-inner-value">Петров І.І.</div>
                                                </div>
                                                <div>
                                                    <div class="record-inner-label">{{ __('patients.clinical_impression_conclusion') }}</div>
                                                    <div class="record-inner-value">{{ __('patients.conducted') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0">
                                                <div class="record-inner-label">ID ECO3</div>
                                                <div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="record-inner-label">ID Епізоду</div>
                                                <div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'vaccinations')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.vaccine') }}</div>
                                            <div class="record-inner-value text-[16px]">SarsCov2_Pr</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.status_done') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-5 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.dosage') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">3 ML</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.route') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Внутрішньом'язево</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.reason') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Згідно календаря щеплень</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.reactions') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">-</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.performer') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Шевченко Т.Г.</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.manufacturer_and_batch') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Данія (55998)</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.body_part') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Праве плече</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.was_performed') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Так</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.date_time_performed') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">10:00 02.04.2025</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.date_time_entered') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">12:00 03.04.2025</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'observation')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.category_and_code') }}</div>
                                            <div class="record-inner-value text-[16px]">Лабораторні дослідження | 85329-1</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.status_valid') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-5 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.information_source') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Пацієнт</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.method') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Інструментальне обстеження</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.value') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">5 мкмоль/л</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.getting_indicators') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">01.02.2025- 02.02.2025</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.updated') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">05.02.2025</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.interpretation') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Краще</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.body_part') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Праве плече</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Сидоренко І.В.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.created') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2025</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'diagnoses')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_name') }}</div>
                                            <div class="record-inner-value text-[16px]">2A00.00 Гліобластома головного мозга</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_clinical') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.type') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.basic') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.verification_status') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.final') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.body_part') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.head') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.created') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">04.02.2026</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Шевченко Т.Г.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.state') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.moderate_severity') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.start_date') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2025</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'condition')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_name') }}</div>
                                            <div class="record-inner-value text-[16px]">A08 - Припухлість</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_clinical') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.type') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.basic') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.verification_status') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.final') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.body_part') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.head') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.created') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">04.02.2026</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Шевченко Т.Г.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.state') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">{{ __('patients.moderate_severity') }}</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.start_date') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2025</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'diagnostic_reports')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_name') }}</div>
                                            <div class="record-inner-value text-[16px]">56001-00 | Комп'ютерна томографія головного мозку</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    Підписано
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-3 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.category') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Візуальні дослідження</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.performer') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Сидоренко О.В.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.created') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2025</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.referral') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">1232132131123</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.conclusion') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Виконано</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'allergies')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_type') }}</div>
                                            <div class="record-inner-value text-[16px]">1232131-3213 | Алергія</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_clinical') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.category') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Побутова</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Сидоренко О.В.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.created') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2025</div></div>
                                                <div></div>

                                                <div><div class="record-inner-label">{{ __('patients.criticality') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Високий</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.verification_status') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Підтверджена</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.source_label') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Зі слів пацієнта</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'risk_assessments')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.code_and_method') }}</div>
                                            <div class="record-inner-value text-[16px]">1232131-3213 | Метод</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_clinical') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.reason') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Причина</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Сидоренко О.В.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.created') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2025</div></div>
                                                <div></div>

                                                <div><div class="record-inner-label">{{ __('patients.result') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Результат</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.qualitative_analysis') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Аналіз</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.source_label') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Зі слів пацієнта</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'devices')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.name') }}</div>
                                            <div class="record-inner-value text-[16px]">Тест-смужки Accu-Chek Active для глюкометра</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.status_valid') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-5 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.model_number') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">1231FDSE</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.type') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Гістероскоп</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.lot_number') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">1231FDSE</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.manufacture_date') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">01.02.2025</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.comment') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Імплант був вилучений по причині заміни на новий</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.properties') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">10 шт</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.manufacturer_and_serial') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">GlobalMed, Inc <br> NSPX30</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Сидоренко І.В.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.expiration_date') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.02.2027</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.status_change_reason') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">-</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @elseif($item['id'] === 'medicines')
                                <div class="record-inner-card">
                                    <div class="record-inner-header">
                                        <div class="p-4 flex items-center justify-center shrink-0 w-14 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                                            <input type="checkbox" class="default-checkbox w-5 h-5">
                                        </div>

                                        <div class="record-inner-column flex-1">
                                            <div class="record-inner-label">{{ __('patients.name') }}</div>
                                            <div class="record-inner-value text-[16px]">Дротаверин 20 мг/мл, розчин для ін'єкцій</div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                                            <div class="record-inner-label">{{ __('patients.status_label') }}</div>
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('patients.active_status') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="record-inner-column-bordered w-full md:w-16 shrink-0 md:!items-center relative">
                                            <button class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                @icon('edit-user-outline', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </div>

                                    <div class="record-inner-body">
                                        <div class="flex-1 p-4 md:pl-[72px] flex justify-center">
                                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-y-4 gap-x-4 w-full [&>div]:min-w-0 [&_div.text-\[13px\]]:break-words">
                                                <div><div class="record-inner-label">{{ __('patients.frequency') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Двічі на день</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.start_of_intake') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.03.2026</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.source_label') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Зі слів пацієнта</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.date_entered') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">03.03.2026</div></div>

                                                <div><div class="record-inner-label">{{ __('patients.dosage') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">50 г/прийом</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.end_of_intake') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">02.04.2026</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.doctor') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Сидоренко І.В.</div></div>
                                                <div><div class="record-inner-label">{{ __('patients.comment') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-200">Коментар</div></div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-52 shrink-0 border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-center gap-3">
                                            <div class="min-w-0"><div class="record-inner-label">ID ECO3</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                            <div class="min-w-0"><div class="record-inner-label">{{ __('patients.medical_record_id') }}</div><div class="text-[13px] font-medium text-gray-800 dark:text-gray-300 truncate">1231-adsadas-aqeqe-casdda</div></div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <div class="py-12 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-dashed border-gray-200 dark:border-gray-700 mt-2">
                                    <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                        <div class="w-8 h-8 mb-4 opacity-50 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                                            @icon($item['icon'])
                                        </div>
                                        <p class="text-[15px] font-medium">Дані відсутні</p>
                                        <p class="text-[13px] mt-1 text-gray-400">В цьому розділі поки немає інформації</p>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Right Sidebar Navigation -->
            <div class="w-full lg:w-[320px] flex-shrink-0 space-y-1 mt-4 lg:mt-0 sticky top-6 self-start">
                @foreach($navItems as $item)
                    <button @if($item['action']) wire:click.once="{{ $item['action'] }}" @endif
                    @click="
                                activeSection = '{{ $item['id'] }}';
                                setTimeout(() => { document.getElementById('block-{{ $item['id'] }}').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 50);
                            "
                            type="button"
                            :class="activeSection === '{{ $item['id'] }}' ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-800 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200'"
                            class="summary-sidebar-btn"
                    >
                        <span class="w-5 h-5 flex items-center justify-center shrink-0">
                            @icon($item['icon'], 'w-5 h-5')
                        </span>
                        <span class="truncate">{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </div>

        </div>
    </div>

    <x-forms.loading />
</x-layouts.patient>
