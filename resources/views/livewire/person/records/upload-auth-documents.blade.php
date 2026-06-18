<div x-data="{ showUploadDocumentsModal: $wire.entangle('showUploadDocumentsModal') }">
    <template x-teleport="body">
        <div x-show="showUploadDocumentsModal"
             style="display: none"
             @keydown.escape.prevent.stop="showUploadDocumentsModal = false"
             role="dialog"
             aria-modal="true"
             class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-transition @click="showUploadDocumentsModal = false" class="modal-wrapper">
                <div @click.stop
                     x-trap.noscroll.inert="showUploadDocumentsModal"
                     class="modal-content w-full max-w-4xl mx-auto"
                >

                    <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white text-center">
                        {{ __('patients.add_auth_method_documents') }}
                    </h2>

                    @foreach($uploadedDocuments as $key => $document)
                        <div class="pb-4 flex" wire:key="auth-document-{{ $key }}">
                            <div class="flex-grow">
                                <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white"
                                       for="authDocument-{{ $key }}"
                                >
                                    {{ __('patients.documents.' . Str::afterLast($document['type'], '.')) }}
                                </label>
                                <div class="flex items-center gap-4">
                                    <input type="file"
                                           class="xl:w-1/2 block text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                                           id="authDocument-{{ $key }}"
                                           wire:model.live="form.uploadedDocuments.{{ $key }}"
                                           accept=".jpeg,.jpg"
                                    >
                                </div>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                    {{ __('forms.max_file_size_and_format') }}
                                </p>

                                @error("form.uploadedDocuments.$key")
                                <p class="text-error">
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-8 flex gap-3">
                        <button type="button"
                                @click="showUploadDocumentsModal = false"
                                class="button-minor"
                        >
                            {{ __('forms.cancel') }}
                        </button>

                        <button type="button"
                                wire:click.prevent="approveCreatingOffline"
                                class="button-primary flex items-center gap-2"
                        >
                            {{ __('forms.send_files') }}
                            @icon('arrow-right', 'w-5 h-5 text-white dark:text-white')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
