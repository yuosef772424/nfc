<div class="flex items-center gap-4 px-4">
    @auth
        @php
            $wallet = app(\App\Services\FinancialSystem\WalletService::class)->getUserWallet(auth()->id());
        @endphp
        @if($wallet)
            <div class="flex items-center gap-2 bg-primary-50 dark:bg-primary-900/20 px-4 py-2 rounded-full">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">الرصيد المتاح:</span>
                <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($wallet['available_balance'], 2) }} YER
                </span>
            </div>
        @endif
        <button 
            type="button"
            onclick="window.location.reload()"
            class="flex items-center gap-1 text-sm text-gray-600 hover:text-primary-600 transition"
        >
            <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5" />
            تحديث
        </button>
    @endauth
</div>