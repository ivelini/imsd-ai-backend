<x-filament-panels::page>
    <form wire:submit="startImport">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Загрузить
            </x-filament::button>
        </div>
    </form>

    {{ $this->table }}
</x-filament-panels::page>
