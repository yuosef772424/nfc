<x-filament-panels::page>
    <div class="space-y-6">
        @if ($errorMessage)
            <div class="p-4 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg text-danger-600 dark:text-danger-400">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            {{ $this->form }}
            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    wire:click="buildQuery"
                    class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition flex items-center gap-2"
                >
                    <x-filament::icon icon="heroicon-o-play" class="w-5 h-5" />
                    تنفيذ الاستعلام
                </button>
            </div>
        </div>

        @if ($showResults)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-medium">نتائج الاستعلام ({{ $totalRecords }} سجل)</h3>
                    <button
                        type="button"
                        wire:click="exportResults"
                        class="px-4 py-2 bg-success-500 hover:bg-success-600 text-white text-sm font-medium rounded-lg transition flex items-center gap-2"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-5 h-5" />
                        تصدير النتائج
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                @foreach ($this->getColumnLabels() as $label)
                                    <th scope="col" class="px-6 py-3">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($queryResult as $row)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    @foreach ((array) $row as $value)
                                        <td class="px-6 py-4">{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="px-6 py-4 text-center">لا توجد نتائج</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($totalRecords > $perPage)
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        {!! $this->paginationView() !!}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>