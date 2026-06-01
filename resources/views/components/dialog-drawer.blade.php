@props([
    'maxWidth' => null,
    'noBackdrop' => false,
    'backdropClickThrough' => false,
    'stopClickPropagation' => false,
    'noTeleport' => false,
    'topClass' => 'top-0',
    'heightClass' => null,
    'zIndex' => '40',
    'panelZIndex' => null,
    'customWidth' => null,
    'overlayWidth' => null,
    'hasClose' => false,
    'onCloseClick' => null,
    'title' => null,
])

@php
    $resolvedWidth = $customWidth;
    if (!$resolvedWidth) {
        $resolvedWidth = [
            'sm' => 'w-72 sm:w-80',
            'md' => 'w-80 sm:w-96',
            'lg' => 'w-96 sm:w-[28rem]',
            'xl' => 'w-full sm:w-[32rem]',
            '2xl' => 'w-full sm:w-[40rem]',
            '3xl' => 'w-full sm:w-[48rem]',
            '4/5' => 'w-full sm:w-4/5',
            '3/5' => 'w-full sm:w-[68%]',
        ][$maxWidth ?? '4/5'];
    }

    $topVal = '0px';
    if ($topClass === 'top-20') {
        $topVal = '80px';
    } elseif ($topClass === 'top-14') {
        $topVal = '56px';
    } elseif ($topClass === 'top-[57px]') {
        $topVal = '57px';
    }

    $heightVal = $heightClass ?? ($topVal !== '0px' ? 'calc(100vh - ' . $topVal . ')' : '100vh');
    $zVal = (int)$zIndex;
    $pZVal = $panelZIndex ?? ($zVal + 1);
@endphp

@if($noTeleport)
    <div
        x-dialog
        x-cloak
        class="fixed left-0 right-0 overflow-hidden {{ $backdropClickThrough ? 'pointer-events-none' : '' }}"
        style="z-index: {{ $zVal }}; top: {{ $topVal }}; height: {{ $heightVal }};"
        {{ $attributes }}
    >
        <!-- Overlay backdrop -->
        @if(!$noBackdrop)
            <div x-dialog:overlay 
                 x-transition.opacity 
                 class="fixed {{ $topClass }} right-0 bg-gray-900/40 {{ $backdropClickThrough ? 'pointer-events-none' : '' }}"
                 style="width: {{ $overlayWidth ?? '80%' }}; height: {{ $heightVal }};"
                 @if($onCloseClick) @click="{{ $onCloseClick }}" @endif
            ></div>
        @endif

        <!-- Panel Container -->
        <div class="fixed {{ $topClass }} right-0 flex {{ $resolvedWidth }} pointer-events-auto"
             style="z-index: {{ $pZVal }}; height: {{ $heightVal }};"
             @if($stopClickPropagation)
                 @click.stop
                 @mousedown.stop
                 @mouseup.stop
                 @pointerdown.stop
             @endif
        >
            <div
                x-dialog:panel
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="h-full w-full"
            >
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl overflow-y-auto {{ $topClass !== 'top-0' ? 'p-8' : 'pt-20 p-8' }} relative">
                    <!-- Close Button -->
                    @if($hasClose)
                        <div class="absolute right-6 top-6 z-10">
                            <button type="button" 
                                    @click="{{ $onCloseClick ?? 'open = false' }}" 
                                    class="relative inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <span class="sr-only">Close drawer</span>
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif

                    <!-- Header -->
                    @isset($title)
                        <h3 class="modal-header mb-6" x-dialog:title>
                            {{ $title }}
                        </h3>
                    @endisset

                    <!-- Slot Content -->
                    <div class="flex-1">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <template x-teleport="body">
        <div
            x-dialog
            x-cloak
            class="fixed left-0 right-0 overflow-hidden {{ $backdropClickThrough ? 'pointer-events-none' : '' }}"
            style="z-index: {{ $zVal }}; top: {{ $topVal }}; height: {{ $heightVal }};"
            {{ $attributes }}
        >
            <!-- Overlay backdrop -->
            @if(!$noBackdrop)
                <div x-dialog:overlay 
                     x-transition.opacity 
                     class="fixed {{ $topClass }} right-0 bg-gray-900/40 {{ $backdropClickThrough ? 'pointer-events-none' : '' }}"
                     style="width: {{ $overlayWidth ?? '80%' }}; height: {{ $heightVal }};"
                     @if($onCloseClick) @click="{{ $onCloseClick }}" @endif
                ></div>
            @endif

            <!-- Panel Container -->
            <div class="fixed {{ $topClass }} right-0 flex {{ $resolvedWidth }} pointer-events-auto"
                 style="z-index: {{ $pZVal }}; height: {{ $heightVal }};"
                 @if($stopClickPropagation)
                     @click.stop
                     @mousedown.stop
                     @mouseup.stop
                     @pointerdown.stop
                 @endif
            >
                <div
                    x-dialog:panel
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="h-full w-full"
                >
                    <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl overflow-y-auto {{ $topClass !== 'top-0' ? 'p-8' : 'pt-20 p-8' }} relative">
                        <!-- Close Button -->
                        @if($hasClose)
                            <div class="absolute right-6 top-6 z-10">
                                <button type="button" 
                                        @click="{{ $onCloseClick ?? 'open = false' }}" 
                                        class="relative inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <span class="sr-only">Close drawer</span>
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @endif

                        <!-- Header -->
                        @isset($title)
                            <h3 class="modal-header mb-6" x-dialog:title>
                                {{ $title }}
                            </h3>
                        @endisset

                        <!-- Slot Content -->
                        <div class="flex-1">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif
