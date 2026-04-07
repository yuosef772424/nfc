<?php

namespace App\Contracts\Services;

use App\Models\WalletTransaction;

interface PaymentServiceInterface
{
    // ---------------------------------------------------------------
    // Card Payment (NFC tap)
    // ---------------------------------------------------------------

    /**
     * دفع عبر بطاقة NFC إلى جهاز تاجر
     *
     * السيناريو: المستخدم يلمس بطاقته على جهاز التاجر
     *
     * @param  string $nfcUid          المعرف الفيزيائي للبطاقة (قبل فك التشفير)
     * @param  string $pin             رقم السر
     * @param  int    $receiverDeviceId جهاز استقبال التاجر
     * @param  float  $amount          المبلغ
     * @throws \App\Exceptions\Payment\InsufficientBalanceException
     * @throws \App\Exceptions\Payment\InvalidCardException
     * @throws \App\Exceptions\Payment\ReceiverDeviceInactiveException
     */
    public function payByCard(
        string $nfcUid,
        string $pin,
        int    $receiverDeviceId,
        float  $amount,
        string $description = ''
    ): WalletTransaction;

    // ---------------------------------------------------------------
    // Mobile NFC Payment
    // ---------------------------------------------------------------

    /**
     * دفع عبر جهاز محمول (بدون بطاقة فيزيائية)
     *
     * السيناريو: المستخدم يُقرّب هاتفه من جهاز التاجر
     *
     * @param  int    $senderDeviceId  جهاز المحمول للمُرسِل
     * @param  int    $receiverDeviceId جهاز استقبال التاجر
     * @param  float  $amount
     * @throws \App\Exceptions\Payment\InsufficientBalanceException
     * @throws \App\Exceptions\Payment\NfcNotSupportedException
     */
    public function payByMobileNfc(
        int    $senderDeviceId,
        int    $receiverDeviceId,
        float  $amount,
        string $description = ''
    ): WalletTransaction;

    // ---------------------------------------------------------------
    // QR / Direct Payment
    // ---------------------------------------------------------------

    /**
     * دفع مباشر بين محفظتين (P2P)
     *
     * @throws \App\Exceptions\Payment\InsufficientBalanceException
     * @throws \App\Exceptions\Payment\SameWalletException
     */
    public function payDirect(
        int    $senderWalletId,
        int    $receiverWalletId,
        float  $amount,
        string $description = ''
    ): WalletTransaction;

    // ---------------------------------------------------------------
    // Refund
    // ---------------------------------------------------------------

    /**
     * استرداد مبلغ معاملة مكتملة
     *
     * @throws \App\Exceptions\Payment\TransactionNotRefundableException
     * @throws \App\Exceptions\Payment\RefundExceedsOriginalException
     */
    public function refund(int $transactionId, ?float $amount = null, string $reason = ''): WalletTransaction;

    // ---------------------------------------------------------------
    // Fee Calculation
    // ---------------------------------------------------------------

    /**
     * حساب الرسوم المتوقعة لعملية دفع
     * يستخدم system_policies للرسوم الحالية
     *
     * @return array{fee: float, net_amount: float, total: float}
     */
    public function calculateFee(float $amount, string $transactionType): array;
}
