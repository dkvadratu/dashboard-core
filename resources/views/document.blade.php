<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ config('app.name') }} · {{ config('dashboard.version') }}</x-slot>
        {!! $this->documentHtml() !!}
    </x-filament::section>
</x-filament-panels::page>
