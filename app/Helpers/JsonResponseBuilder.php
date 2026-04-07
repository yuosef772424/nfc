<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class JsonResponseBuilder
{
    /**
     * استجابة نجاح
     */
    public static function success($data = null, string $message = 'Success', int $code = 200, ?array $meta = null): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
            'meta'    => $meta,
        ], $code);
    }

    /**
     * استجابة خطأ عام
     */
    public static function error(string $message = 'Error', int $code = 400, $errors = null, $data = null): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
            'meta'    => null,
        ], $code);
    }

    /**
     * استجابة خطأ تحقق المدخلات (Validation)
     */
    public static function validationError($errors, string $message = 'Validation failed', int $code = 422): JsonResponse
    {
        if ($errors instanceof ValidationException) {
            $errors = $errors->errors();
        } elseif ($errors instanceof MessageBag) {
            $errors = $errors->toArray();
        }

        return self::error($message, $code, $errors);
    }

    /**
     * استجابة Pagination
     */
    public static function paginated($paginator, $data, string $message = 'Success'): JsonResponse
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];
        return self::success($data, $message, 200, $meta);
    }
}