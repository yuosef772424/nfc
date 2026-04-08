<?php

namespace App\Http\Requests;

use App\Rules\ValidationRules;

class FinancialRequests
{
    // ==================== Wallet ====================
    public static function storeWallet(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'currency' => ValidationRules::currency(),
            'status' => ValidationRules::walletStatus(),
            'available_balance' => 'sometimes|numeric|min:0',
            'pending_balance' => 'sometimes|numeric|min:0',
        ];
    }

    public static function updateWallet(): array
    {
        return [
            'status' => ValidationRules::walletStatus(),
            'currency' => ValidationRules::currency(),
        ];
    }

    // ==================== WalletTransaction ====================
    public static function storeTransaction(): array
    {
        return [
            'sender_wallet_id' => 'required|integer|exists:wallets,id',
            'receiver_wallet_id' => 'required|integer|exists:wallets,id|different:sender_wallet_id',
            'amount' => 'required|numeric|min:0.01',
            'type' => ValidationRules::transactionType(),
            'status' => ValidationRules::transactionStatus(),
            'transaction_uuid' => 'sometimes|uuid|unique:wallet_transactions,transaction_uuid',
        ];
    }

    public static function updateTransaction(): array
    {
        return [
            'status' => ValidationRules::transactionStatus(),
            'failure_reason' => 'nullable|string|max:255',
            'failure_code' => 'nullable|string|max:50',
        ];
    }

    // ==================== Withdrawal ====================
    public static function storeWithdrawal(): array
    {
        return [
            'wallet_id' => 'required|integer|exists:wallets,id',
            'agent_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'verification_code' => 'required|string|min:4|max:10',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    public static function updateWithdrawal(): array
    {
        return [
            'status' => ValidationRules::withdrawalStatus(),
        ];
    }

    // ==================== CommissionLog ====================
    public static function storeCommissionLog(): array
    {
        return [
            'recipient_id' => 'required|integer',
            'recipient_type' => ValidationRules::recipientType(),
            'amount' => 'required|numeric|min:0',
            'status' => ValidationRules::commissionStatus(),
            'reference_type' => 'required|string|in:withdrawal,transaction',
            'reference_id' => 'required|integer',
        ];
    }

    public static function updateCommissionLog(): array
    {
        return [
            'status' => ValidationRules::commissionStatus(),
        ];
    }

    // ==================== LedgerEntry ====================
    public static function storeLedgerEntry(): array
    {
        return [
            'transaction_id' => 'required|integer|exists:wallet_transactions,id',
            'wallet_id' => 'required|integer|exists:wallets,id',
            'entry_type' => 'required|string|in:debit,credit',
            'amount' => 'required|numeric|min:0',
            'balance_after' => 'required|numeric',
        ];
    }
}