<fieldset class="fieldset" id="referral-section">
    <legend class="legend">
        {{ __('patients.referrals') }}
    </legend>

    <div x-data="{
        isReferralAvailable: $wire.entangle('form.isReferralAvailable'),
        referralType: $wire.entangle('form.referralType')
    }">
        <div class="mb-8">
            <div class="form-group group">
                <input x-model="isReferralAvailable"
                       type="checkbox"
                       name="isReferralAvailable"
                       id="isReferralAvailable"
                       class="default-checkbox mb-1"
                />
                <label class="default-p font-medium" for="isReferralAvailable">
                    {{ __('patients.referral_available') }}
                </label>
            </div>
        </div>

        <div x-show="isReferralAvailable" x-transition x-cloak>
            <div class="form-row-2 mb-10">
                <div class="form-group group">
                    <select x-model="referralType"
                            id="referralType"
                            class="input-select peer"
                    >
                        <option value="">{{ __('forms.select') }}</option>
                        <option value="electronic">{{ __('patients.electronic_referral') }}</option>
                        <option value="paper">{{ __('patients.paper_referral') }}</option>
                    </select>
                    <label for="referralType" class="label">
                        {{ __('patients.referral_type') }}
                    </label>
                </div>
            </div>

            <template x-if="referralType === 'electronic'">
                <div class="form-row-2">
                    <div class="form-group group">
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none">
                                @icon('search', 'w-4 h-4 text-gray-400')
                            </div>
                            <input wire:model="form.referralNumber"
                                   type="text"
                                   name="requisitionNumber"
                                   id="requisitionNumber"
                                   class="input !pl-7 !pr-7 peer @error('form.referralNumber') input-error @enderror"
                                   placeholder=" "
                                   autocomplete="off"
                            />
                            <label for="requisitionNumber" class="label !left-7">
                                {{ __('patients.referral_number') }}
                            </label>
                            <div class="absolute inset-y-0 end-0 flex items-center">
                                <button type="button" @click="$wire.set('form.referralNumber', '')" class="text-gray-400 hover:text-gray-600">
                                    @icon('close', 'w-4 h-4')
                                </button>
                            </div>
                        </div>
                        @error('form.referralNumber')
                            <p class="text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </template>

            <template x-if="referralType === 'paper'">
                <div class="space-y-8">
                    <div class="form-row-2">
                        <div class="form-group group">
                            <div class="relative">
                                <input wire:model="form.paperReferralNumber"
                                       type="text"
                                       id="paperReferralNumber"
                                       class="input !pr-7 peer"
                                       placeholder=" "
                                />
                                <label for="paperReferralNumber" class="label">
                                    {{ __('patients.referral_number') }}*
                                </label>
                                <div class="absolute inset-y-0 end-0 flex items-center">
                                    <button type="button" @click="$wire.set('form.paperReferralNumber', '')" class="text-gray-400 hover:text-gray-600">
                                        @icon('close', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group group">
                            <div class="relative">
                                <input wire:model="form.paperReferralAuthor"
                                       type="text"
                                       id="paperReferralAuthor"
                                       class="input !pr-7 peer"
                                       placeholder=" "
                                />
                                <label for="paperReferralAuthor" class="label">
                                    {{ __('patients.paper_referral_author') }}*
                                </label>
                                <div class="absolute inset-y-0 end-0 flex items-center">
                                    <button type="button" @click="$wire.set('form.paperReferralAuthor', '')" class="text-gray-400 hover:text-gray-600">
                                        @icon('close', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group group">
                            <div class="relative">
                                <input wire:model="form.paperReferralEdrpou"
                                       type="text"
                                       id="paperReferralEdrpou"
                                       class="input !pr-7 peer"
                                       placeholder=" "
                                />
                                <label for="paperReferralEdrpou" class="label">
                                    {{ __('patients.paper_referral_edrpou_short') }}*
                                </label>
                                <div class="absolute inset-y-0 end-0 flex items-center">
                                    <button type="button" @click="$wire.set('form.paperReferralEdrpou', '')" class="text-gray-400 hover:text-gray-600">
                                        @icon('close', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group group">
                            <div class="relative">
                                <input wire:model="form.paperReferralInstitutionName"
                                       type="text"
                                       id="paperReferralInstitutionName"
                                       class="input !pr-7 peer"
                                       placeholder=" "
                                />
                                <label for="paperReferralInstitutionName" class="label">
                                    {{ __('patients.paper_referral_institution_short') }}
                                </label>
                                <div class="absolute inset-y-0 end-0 flex items-center">
                                    <button type="button" @click="$wire.set('form.paperReferralInstitutionName', '')" class="text-gray-400 hover:text-gray-600">
                                        @icon('close', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group group">
                            <div class="datepicker-wrapper">
                                <input wire:model="form.paperReferralDate"
                                       type="text"
                                       datepicker
                                       datepicker-format="dd.mm.yyyy"
                                       id="paperReferralDate"
                                       class="datepicker-input with-leading-icon input peer"
                                       placeholder=" "
                                       autocomplete="off"
                                />
                                <label for="paperReferralDate" class="wrapped-label">
                                    {{ __('patients.paper_referral_date') }}*
                                </label>
                            </div>
                        </div>

                        <div class="form-group group">
                            <div class="relative">
                                <input wire:model="form.paperReferralNotes"
                                       type="text"
                                       id="paperReferralNotes"
                                       class="input !pr-7 peer"
                                       placeholder=" "
                                />
                                <label for="paperReferralNotes" class="label">
                                    {{ __('patients.paper_referral_notes') }}
                                </label>
                                <div class="absolute inset-y-0 end-0 flex items-center">
                                    <button type="button" @click="$wire.set('form.paperReferralNotes', '')" class="text-gray-400 hover:text-gray-600">
                                        @icon('close', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</fieldset>
