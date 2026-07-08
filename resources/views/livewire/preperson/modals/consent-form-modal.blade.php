<template x-teleport="body">
    <div x-show="showConsentFormModal"
         style="display: none"
         @keydown.escape.prevent.stop="showConsentFormModal = false"
         role="dialog"
         aria-modal="true"
         class="modal"
    >
        <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
        <div x-transition @click="showConsentFormModal = false" class="modal-wrapper">
            <div @click.stop x-trap.noscroll.inert="showConsentFormModal"
                 class="modal-content w-full max-w-4xl mx-auto rounded-2xl shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden p-8 space-y-6"
                 x-data="{
                     printModal() {
                         let printWindow = window.open('', '_blank');
                         printWindow.document.write('<' + 'html><' + 'head><title>{{ addslashes(__('preperson.merge.consent_declaration_header')) }}</title>');
                         printWindow.document.write('<link href=&quot;https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css&quot; rel=&quot;stylesheet&quot;>');
                         printWindow.document.write('<' + '/head><' + 'body class=&quot;p-12 text-gray-900 bg-white&quot;>');
                         printWindow.document.write(document.getElementById('consent-print-area').innerHTML);
                         printWindow.document.write('<' + '/body><' + '/html>');
                         printWindow.document.close();
                         printWindow.focus();
                         setTimeout(() => {
                             printWindow.print();
                             printWindow.close();
                         }, 500);
                     }
                 }"
            >
                <div id="consent-print-area" class="space-y-6">
                    <h3 class="text-xl font-bold text-center text-gray-900 dark:text-white leading-snug">
                        {{ __('preperson.merge.consent_declaration_header') }}
                    </h3>

                    <div class="text-sm text-gray-800 dark:text-gray-300 space-y-5 leading-relaxed">
                        <p>
                            {{ __('preperson.merge.consent_i') }} <span class="font-bold dark:text-white" x-text="`${$wire.selectedMergePatient?.lastName || 'Великованенко'} ${$wire.selectedMergePatient?.firstName || 'Ірина'} ${$wire.selectedMergePatient?.secondName || 'Николаевич'}`"></span>{{ __('preperson.merge.consent_to_facility') }}
                            <span class="font-bold dark:text-white">КОМУНАЛЬНЕ НЕКОМЕРЦІЙНЕ ПІДПРИЄМСТВО "ТЕРИТОРІАЛЬНЕ МЕДИЧНЕ ОБ'ЄДНАННЯ "БАГАТОПРОФІЛЬНА ЛІКАРНЯ ІНТЕНСИВНИХ МЕТОДІВ ЛІКУВАННЯ ТА ШВИДКОЇ МЕДИЧНОЇ ДОПОМОГИ" МЕЛІТОПОЛЬСЬКОЇ МІСЬКОЇ РАДИ ЗАПОРІЗЬКОЇ ОБЛАСТІ, ЄДРПОУ: 05498720</span>{{ __('preperson.merge.consent_suffix') }}
                        </p>

                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold w-1/3">{{ __('preperson.merge.episode_name') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">210.0 - Професійне медичне обстеження</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.responsible_organization') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">КОМУНАЛЬНЕ НЕКОМЕРЦІЙНЕ ПІДПРИЄМСТВО "ТЕРИТОРІАЛЬНЕ МЕДИЧНЕ ОБ'ЄДНАННЯ "БАГАТОПРОФІЛЬНА ЛІКАРНЯ ІНТЕНСИВНИХ МЕТОДІВ ЛІКУВАННЯ ТА ШВИДКОЇ МЕДИЧНОЇ ДОПОМОГИ" МЕЛІТОПОЛЬСЬКОЇ МІСЬКОЇ РАДИ ЗАПОРІЗЬКОЇ ОБЛАСТІ</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.period') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">{{ __('preperson.merge.since_date') }}</td>
                                    </tr>
                                    <!-- Episode 2 -->
                                    <tr class="border-t border-gray-200 dark:border-gray-700">
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.episode_name') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">030.2 | Чотириплідна вагітність</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.responsible_organization') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">КОМУНАЛЬНЕ НЕКОМЕРЦІЙНЕ ПІДПРИЄМСТВО "ТЕРИТОРІАЛЬНЕ МЕДИЧНЕ ОБ'ЄДНАННЯ "БАГАТОПРОФІЛЬНА ЛІКАРНЯ ІНТЕНСИВНИХ МЕТОДІВ ЛІКУВАННЯ ТА ШВИДКОЇ МЕДИЧНОЇ ДОПОМОГИ" МЕЛІТОПОЛЬСЬКОЇ МІСЬКОЇ РАДИ ЗАПОРІЗЬКОЇ ОБЛАСТІ</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.period') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">26.05.2020 - 27.06.2020</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p>{{ __('preperson.merge.unidentified_entered_in_ehealth') }}</p>

                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold w-1/3">{{ __('preperson.merge.patient_local_id') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">38582529.3496007456.0000000861</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.patient_first_name') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">-</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.patient_last_name') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">-</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.patient_second_name') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">-</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ strtoupper(__('forms.gender')) }}:</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">{{ __('preperson.merge.gender_male_value') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-gray-200 font-bold">{{ __('preperson.merge.additional_info') }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-gray-300">{{ __('preperson.merge.newborn_info_text') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p>
                            {{ __('preperson.merge.consent_processing_text') }}
                        </p>

                        <div class="pt-8 flex flex-col gap-2 max-w-xs">
                            <div class="border-b border-gray-800 dark:border-gray-650 w-full h-8"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ __('preperson.merge.patient_signature') }}</div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-2" x-text="new Date().toLocaleDateString('{{ app()->getLocale() === 'uk' ? 'uk-UA' : 'en-US' }}')"></div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <button type="button"
                            @click="showConsentFormModal = false"
                            class="button-minor"
                    >
                        {{ __('forms.close') }}
                    </button>
                    <button type="button"
                            @click="printModal()"
                            class="inline-flex items-center gap-2 border border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20 text-blue-600 dark:text-blue-400 px-4 py-2.5 rounded-lg transition-colors font-semibold text-sm cursor-pointer"
                    >
                        @icon('printer', 'w-4 h-4')
                        <span>{{ __('preperson.merge.print') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
