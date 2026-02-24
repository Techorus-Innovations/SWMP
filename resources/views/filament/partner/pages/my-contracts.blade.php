<x-filament-panels::page>
    <x-filament::tabs>
        @foreach ($this->getTabs() as $tab)
            <x-filament::tabs.item
                :active="$this->activeTab === $tab['value']"
                wire:click="setTab('{{ $tab['value'] }}')"
            >
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
