<x-filament-panels::page>
    <x-slot name="headerActions">
        @foreach($this->getCachedHeaderActions() as $action)
            {{ $action }}
        @endforeach
    </x-slot>
    
    {{ $this->infolist }}

    {{ $this->table }}
</x-filament-panels::page>
