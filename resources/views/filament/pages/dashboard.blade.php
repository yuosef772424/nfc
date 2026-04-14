<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        @foreach($this->getWidgets() as $widget)
            {{ $widget }}
        @endforeach
    </div>
</x-filament-panels::page>