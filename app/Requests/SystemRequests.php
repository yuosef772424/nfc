<?php

namespace App\Http\Requests;

use App\Rules\ValidationRules;

class SystemRequests
{
    // ==================== Session ====================
    public static function storeSession(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'token_hash' => 'required|string|unique:sessions,token_hash',
            'device_info' => 'nullable|array',
            'location' => 'nullable|array',
            'expires_at' => 'required|date|after:now',
        ];
    }

    // ==================== Notification ====================
    public static function storeNotification(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'type' => ValidationRules::notificationType(),
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'channel' => ValidationRules::notificationChannel(),
            'data' => 'nullable|array',
        ];
    }

    public static function updateNotification(): array
    {
        return [
            'is_read' => 'sometimes|boolean',
        ];
    }

    // ==================== AuditLog ====================
    public static function storeAuditLog(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'action' => 'required|string|max:100',
            'entity' => 'required|string|max:100',
            'entity_id' => 'required|integer',
            'old_data' => 'nullable|array',
            'new_data' => 'nullable|array',
            'ip_address' => 'required|ip',
        ];
    }

    // ==================== AppConfig ====================
    public static function storeAppConfig(): array
    {
        return [
            'group' => 'required|string|max:100',
            'key' => 'required|string|max:100',
            'value' => 'required',
            'data_type' => 'required|string|in:boolean,number,string,json',
            'label' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public static function updateAppConfig(): array
    {
        return [
            'value' => 'sometimes|required',
            'data_type' => 'sometimes|string|in:boolean,number,string,json',
            'label' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}