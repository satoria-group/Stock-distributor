@props([
    'placeholder' => 'DD/MM/YYYY',
    'id' => null,
    'title' => null,
    'class' => '',
])

@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel ? $wireModel->value() : null;
    $isLive = $wireModel ? $wireModel->hasModifier('live') : false;
    $inputId = $id ?? 'dp_' . ($modelName ? str_replace('.', '_', $modelName) : uniqid());
@endphp

<div wire:ignore
     x-data="datePicker({
        value: @if($modelName) @if($isLive) @entangle($modelName).live @else @entangle($modelName) @endif @else null @endif,
        inputClass: @js($class)
     })"
     class="relative inline-block w-full">
    <input x-ref="inputEl"
           type="text"
           id="{{ $inputId }}"
           placeholder="{{ $placeholder }}"
           @if($title) title="{{ $title }}" @endif
           class="hidden"
           {{ $attributes->whereDoesntStartWith('wire:model')->except(['class', 'placeholder', 'id', 'title']) }}>

    <div @click="fp && fp.open()"
         class="absolute inset-y-0 right-0 pr-2.5 flex items-center cursor-pointer text-slate-400 hover:text-[#0d6d5f] transition select-none">
        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
    </div>
</div>
