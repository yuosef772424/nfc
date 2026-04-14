<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class QueryBuilderService
{
    /**
     * قائمة الجداول المسموح بها مع أسمائها المستعرضة.
     */
    public function getAllowedTables(): array
    {
        return [
            'users' => 'المستخدمين',
            'wallets' => 'المحافظ',
            'wallet_transactions' => 'المعاملات',
            'cards' => 'البطاقات',
            'withdrawals' => 'السحوبات',
            'commission_logs' => 'العمولات',
            'nfc_devices' => 'أجهزة NFC',
            'agent_profiles' => 'ملفات الوكلاء',
            'merchant_profiles' => 'ملفات التجار',
            'user_kyc' => 'التحقق من الهوية',
            'ledger_entries' => 'دفتر الأستاذ',
            'audit_log' => 'سجل التدقيق',
            'notifications' => 'الإشعارات',
            'sessionss' => 'الجلسات',
        ];
    }

    /**
     * الحصول على أعمدة جدول معين.
     */
    public function getTableColumns(string $table): array
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * اقتراح شروط ربط بين جدولين بناءً على أسماء الأعمدة الشائعة.
     */
    public function suggestJoinCondition(string $baseTable, string $joinTable): ?array
    {
        $baseColumns = $this->getTableColumns($baseTable);
        $joinColumns = $this->getTableColumns($joinTable);

        // قواعد بسيطة
        $foreignKeyMap = [
            'users' => ['id'],
            'wallets' => ['user_id'],
            'wallet_transactions' => ['sender_wallet_id', 'receiver_wallet_id'],
            'cards' => ['wallet_id', 'agent_id'],
            'withdrawals' => ['wallet_id', 'agent_id'],
            'agent_profiles' => ['user_id'],
            'merchant_profiles' => ['user_id'],
            'nfc_devices' => ['user_id'],
            'commission_logs' => ['recipient_id'],
            'user_kyc' => ['user_id'],
            'ledger_entries' => ['wallet_id', 'transaction_id'],
            'notifications' => ['user_id'],
            'sessionss' => ['user_id'],
        ];

        // محاولة إيجاد تطابق شائع
        if (isset($foreignKeyMap[$joinTable])) {
            foreach ($foreignKeyMap[$joinTable] as $fk) {
                if (in_array($fk, $joinColumns)) {
                    $targetTable = $baseTable;
                    $targetColumn = 'id';
                    if ($fk === 'user_id' && in_array('id', $baseColumns)) {
                        return ['first' => "{$baseTable}.id", 'second' => "{$joinTable}.{$fk}"];
                    }
                    if ($fk === 'wallet_id' && in_array('id', $baseColumns)) {
                        return ['first' => "{$baseTable}.id", 'second' => "{$joinTable}.{$fk}"];
                    }
                    // إضافة المزيد من الأنماط حسب الحاجة
                }
            }
        }

        // محاولة عكسية
        if (isset($foreignKeyMap[$baseTable])) {
            foreach ($foreignKeyMap[$baseTable] as $fk) {
                if (in_array($fk, $baseColumns)) {
                    if (in_array('id', $joinColumns)) {
                        return ['first' => "{$baseTable}.{$fk}", 'second' => "{$joinTable}.id"];
                    }
                }
            }
        }

        return null;
    }

    /**
     * بناء الاستعلام بناءً على المواصفات المقدمة.
     *
     * @param array $spec يحتوي على:
     *   - base_table: string
     *   - joins: array (اختياري) كل عنصر: [table, type, first, operator, second]
     *   - conditions: array (اختياري) كل عنصر: [table, column, operator, value, boolean]
     *   - select_columns: array (اختياري) كل عنصر: [table, column, alias]
     *   - order_by: array (اختياري)
     *   - limit: int (افتراضي 500)
     */
    public function buildQuery(array $spec): Builder
    {
        $baseTable = $spec['base_table'] ?? throw new \InvalidArgumentException('الجدول الأساسي مطلوب.');

        $query = DB::table($baseTable);

        // تطبيق JOINs
        foreach ($spec['joins'] ?? [] as $join) {
            if (empty($join['table']) || empty($join['first']) || empty($join['second'])) {
                continue;
            }
            $type = $join['type'] ?? 'inner';
            $operator = $join['operator'] ?? '=';
            $query->join($join['table'], $join['first'], $operator, $join['second'], $type);
        }

        // تطبيق WHERE conditions
        foreach ($spec['conditions'] ?? [] as $condition) {
            if (empty($condition['table']) || empty($condition['column'])) {
                continue;
            }
            $fullColumn = $condition['table'] . '.' . $condition['column'];
            $operator = strtoupper($condition['operator']);
            $value = $condition['value'] ?? null;
            $boolean = $condition['boolean'] ?? 'AND';

            if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
                $method = ($operator === 'IS NULL') ? 'whereNull' : 'whereNotNull';
                $query->{$method}($fullColumn, $boolean);
            } elseif (in_array($operator, ['IN', 'NOT IN'])) {
                $values = is_array($value) ? $value : array_map('trim', explode(',', (string)$value));
                $method = ($operator === 'IN') ? 'whereIn' : 'whereNotIn';
                $query->{$method}($fullColumn, $values, $boolean);
            } else {
                $query->where($fullColumn, $operator, $value, $boolean);
            }
        }

        // تحديد الأعمدة
        $columns = [];
        foreach ($spec['select_columns'] ?? [] as $col) {
            if (empty($col['table']) || empty($col['column'])) {
                continue;
            }
            $full = $col['table'] . '.' . $col['column'];
            if (!empty($col['alias'])) {
                $full .= ' as ' . $col['alias'];
            }
            $columns[] = $full;
        }

        if (empty($columns)) {
            $columns = ['*'];
        }

        $query->select($columns);

        // ترتيب
        if (!empty($spec['order_by'])) {
            foreach ($spec['order_by'] as $order) {
                if (!empty($order['column']) && !empty($order['direction'])) {
                    $query->orderBy($order['column'], $order['direction']);
                }
            }
        }

        // تحديد عدد السجلات (للأمان)
        $limit = $spec['limit'] ?? 500;
        $query->limit($limit);

        return $query;
    }
}