<?php

namespace App\Traits;

trait PaginatorTrait
{
    /**
     * استخراج معاملات pagination من الطلب أو من مصفوفة.
     */
    protected function getPaginationParams(array $params, int $defaultPerPage = 20): array
    {
        return [
            'perPage' => $params['per_page'] ?? $defaultPerPage,
            'page'    => $params['page'] ?? 1,
        ];
    }

    /**
     * تنسيق مخرجات paginator إلى مصفوفة موحدة.
     */
    protected function formatPaginator($paginator): array
    {
        return [
            'data'       => $paginator->items(),
            'meta'       => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }
}