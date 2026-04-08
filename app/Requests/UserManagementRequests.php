<?php

namespace App\Http\Requests;

use App\Rules\ValidationRules;

class UserManagementRequests
{
    // ==================== User ====================
    public static function storeUser(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => ValidationRules::userType(),
            'status' => ValidationRules::userStatus(),
            'is_verified' => 'sometimes|boolean',
        ];
    }

    public static function updateUser(int $userId = null): array
    {
        $uniqueEmail = $userId ? "unique:users,email,{$userId}" : 'unique:users,email';
        $uniquePhone = $userId ? "unique:users,phone,{$userId}" : 'unique:users,phone';
        return [
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|{$uniqueEmail}",
            'phone' => "sometimes|string|{$uniquePhone}",
            'password' => 'sometimes|string|min:8|confirmed',
            'user_type' => ['sometimes', ValidationRules::userType()],
            'status' => ['sometimes', ValidationRules::userStatus()],
            'is_verified' => 'sometimes|boolean',
        ];
    }

    // ==================== AgentProfile ====================
    public static function storeAgentProfile(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'commission_type' => 'sometimes|string|in:percentage,fixed',
            'commission_value' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public static function updateAgentProfile(): array
    {
        return [
            'commission_type' => 'sometimes|string|in:percentage,fixed',
            'commission_value' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }

    // ==================== MerchantProfile ====================
    public static function storeMerchantProfile(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public static function updateMerchantProfile(): array
    {
        return [
            'business_name' => 'sometimes|string|max:255',
            'business_type' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }

    // ==================== UserKyc ====================
    public static function storeUserKyc(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'id_type' => 'required|string|in:passport,national_id,driving_license',
            'id_number' => 'required|string|max:50',
            'id_expiry_date' => 'required|date|after:today',
            'verified_at' => 'nullable|date',
        ];
    }

    public static function updateUserKyc(): array
    {
        return [
            'id_type' => 'sometimes|string|in:passport,national_id,driving_license',
            'id_number' => 'sometimes|string|max:50',
            'id_expiry_date' => 'sometimes|date|after:today',
            'verified_at' => 'nullable|date',
        ];
    }
}