<?php

namespace App\Filament\Core;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Cache;

class ConfigConstants
{
    protected const CACHE_KEY = 'config_constants_formatted';

    /**
     * قيم افتراضية مؤقتة للثوابت الأساسية (تستخدم إذا كانت قاعدة البيانات فارغة).
     */
    protected static array $fallback = [
        'constant' => [
            'user_type.customer' => ['value' => 'customer', 'label' => 'عميل'],
            'user_type.agent'    => ['value' => 'agent',    'label' => 'وكيل'],
            'user_type.merchant' => ['value' => 'merchant', 'label' => 'تاجر'],

            'user_status.active'    => ['value' => 'active',    'label' => 'نشط'],
            'user_status.inactive'  => ['value' => 'inactive',  'label' => 'غير نشط'],
            'user_status.suspended' => ['value' => 'suspended', 'label' => 'موقوف'],
            'user_status.deleted'   => ['value' => 'deleted',   'label' => 'محذوف'],

            'wallet_status.active'   => ['value' => 'active',   'label' => 'نشطة'],
            'wallet_status.inactive' => ['value' => 'inactive', 'label' => 'غير نشطة'],
            'wallet_status.frozen'   => ['value' => 'frozen',   'label' => 'مجمدة'],

            'card_status.active'  => ['value' => 'active',  'label' => 'نشطة'],
            'card_status.blocked' => ['value' => 'blocked', 'label' => 'محظورة'],
            'card_status.expired' => ['value' => 'expired', 'label' => 'منتهية'],

            'transaction_type.deposit'    => ['value' => 'deposit',    'label' => 'إيداع'],
            'transaction_type.withdrawal' => ['value' => 'withdrawal', 'label' => 'سحب'],
            'transaction_type.transfer'   => ['value' => 'transfer',   'label' => 'تحويل'],
            'transaction_type.payment'    => ['value' => 'payment',    'label' => 'دفع'],
            'transaction_type.refund'     => ['value' => 'refund',     'label' => 'استرداد'],

            'transaction_status.pending'   => ['value' => 'pending',   'label' => 'معلقة'],
            'transaction_status.completed' => ['value' => 'completed', 'label' => 'مكتملة'],
            'transaction_status.failed'    => ['value' => 'failed',    'label' => 'فاشلة'],
            'transaction_status.cancelled' => ['value' => 'cancelled', 'label' => 'ملغية'],

            'withdrawal_status.pending'   => ['value' => 'pending',   'label' => 'معلق'],
            'withdrawal_status.completed' => ['value' => 'completed', 'label' => 'مكتمل'],
            'withdrawal_status.failed'    => ['value' => 'failed',    'label' => 'فاشل'],
            'withdrawal_status.cancelled' => ['value' => 'cancelled', 'label' => 'ملغي'],

            'commission_type.percentage' => ['value' => 'percentage', 'label' => 'نسبة مئوية'],
            'commission_type.fixed'      => ['value' => 'fixed',      'label' => 'مبلغ ثابت'],

            'business_type.retail'     => ['value' => 'retail',     'label' => 'تجزئة'],
            'business_type.wholesale'  => ['value' => 'wholesale',  'label' => 'جملة'],
            'business_type.service'    => ['value' => 'service',    'label' => 'خدمات'],
            'business_type.restaurant' => ['value' => 'restaurant', 'label' => 'مطعم'],
            'business_type.other'      => ['value' => 'other',      'label' => 'أخرى'],
        ],
    ];

    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $configs = AppConfig::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $formatted = [];
            foreach ($configs as $config) {
                $group = $config->group;
                $key   = $config->key;

                if (!isset($formatted[$group])) {
                    $formatted[$group] = [];
                }
                $formatted[$group][$key] = [
                    'value'     => $config->casted_value,
                    'raw_value' => $config->value,
                    'data_type' => $config->data_type,
                    'label'     => $config->label,
                    'meta'      => $config->meta,
                ];
            }

            // دمج القيم الاحتياطية للمجموعات التي لم تُجلب من القاعدة
            foreach (self::$fallback as $group => $items) {
                if (!isset($formatted[$group])) {
                    $formatted[$group] = [];
                }
                foreach ($items as $key => $item) {
                    if (!isset($formatted[$group][$key])) {
                        $formatted[$group][$key] = [
                            'value'     => $item['value'],
                            'raw_value' => $item['value'],
                            'data_type' => 'string',
                            'label'     => $item['label'],
                            'meta'      => null,
                        ];
                    }
                }
            }

            return $formatted;
        });
    }

    public static function group(string $group): array
    {
        return self::all()[$group] ?? [];
    }

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        return self::all()[$group][$key]['value'] ?? $default;
    }

    public static function options(string $group, string $prefix = null): array
    {
        $items = self::group($group);
        $options = [];

        foreach ($items as $key => $item) {
            if ($prefix !== null) {
                if (!str_starts_with($key, $prefix . '.')) {
                    continue;
                }
                $optionKey = substr($key, strlen($prefix) + 1);
            } else {
                $optionKey = $key;
            }

            $options[$item['value']] = $item['label'] ?? $optionKey;
        }

        return $options;
    }

    public static function colorMap(string $group, string $prefix = null): array
    {
        $items = self::group($group);
        $map = [];

        foreach ($items as $key => $item) {
            if ($prefix !== null && !str_starts_with($key, $prefix . '.')) {
                continue;
            }
            $value = $item['value'];
            $color = $item['meta']['color'] ?? 'gray';
            $map[$value] = $color;
        }

        return $map;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}