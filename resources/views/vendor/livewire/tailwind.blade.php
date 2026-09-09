@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            <span>
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-400 bg-white border border-slate-200 cursor-default leading-5 rounded-xl">
                        {!! __('pagination.previous') !!}
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 leading-5 rounded-xl hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
                        {!! __('pagination.previous') !!}
                    </button>
                @endif
            </span>

            <span>
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-semibold text-slate-700 bg-white border border-slate-200 leading-5 rounded-xl hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
                        {!! __('pagination.next') !!}
                    </button>
                @else
                    <span class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-semibold text-slate-400 bg-white border border-slate-200 cursor-default leading-5 rounded-xl">
                        {!! __('pagination.next') !!}
                    </span>
                @endif
            </span>
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-500 font-medium">
                    <span>{!! __('Menampilkan') !!}</span>
                    <span class="font-bold text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span>
                    <span>{!! __('sampai') !!}</span>
                    <span class="font-bold text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span>
                    <span>{!! __('dari') !!}</span>
                    <span class="font-bold text-slate-700">{{ $paginator->total() }}</span>
                    <span>{!! __('data') !!}</span>
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-xl shadow-2xs -space-x-px">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-300 bg-white border border-slate-200 cursor-default rounded-l-xl leading-5" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-l-xl leading-5 hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none transition cursor-pointer" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-400 bg-white border border-slate-200 cursor-default leading-5">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page">
                                            <span class="relative inline-flex items-center px-3.5 py-2 text-xs font-bold text-white border border-[#0d6d5f] cursor-default leading-5" style="background-color: #0d6d5f;">{{ $page }}</span>
                                        </span>
                                    @else
                                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 leading-5 hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none transition cursor-pointer" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                            {{ $page }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-r-xl leading-5 hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none transition cursor-pointer" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-300 bg-white border border-slate-200 cursor-default rounded-r-xl leading-5" aria-hidden="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
