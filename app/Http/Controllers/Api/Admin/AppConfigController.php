<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\System\AppConfigService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppConfigController extends BaseApiController
{
    public function __construct(protected AppConfigService $configService) {}

    /**
     * عرض جميع الإعدادات مجمعة.
     */
    public function index()
    {
        $configs = $this->configService->getAllGrouped();
        return $this->successResponse($configs);
    }

    /**
     * عرض مجموعة محددة.
     */
    public function group(string $group)
    {
        $configs = $this->configService->getGroup($group);
        return $this->successResponse($configs);
    }

    /**
     * عرض قيمة محددة.
     */
    public function show(Request $request)
    {
        $request->validate([
            'group' => 'required|string',
            'key'   => 'required|string',
        ]);

        $value = $this->configService->get(
            $request->input('group'),
            $request->input('key')
        );

        return $this->successResponse(['value' => $value]);
    }

    /**
     * إنشاء أو تحديث إعداد.
     */
    public function store(Request $request)
    {
        $request->validate([
            'group' => 'required|string|max:100',
            'key'   => 'required|string|max:100',
            'value' => 'required',
            'label' => 'nullable|string|max:255',
            'meta'  => 'nullable|array',
        ]);

        try {
            $config = $this->configService->set(
                group: $request->input('group'),
                key: $request->input('key'),
                value: $request->input('value'),
                meta: $request->input('meta', []),
                label: $request->input('label')
            );
            return $this->successResponse($config, 'Configuration saved.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تعطيل إعداد.
     */
    public function deactivate(Request $request)
    {
        $request->validate([
            'group' => 'required|string',
            'key'   => 'required|string',
        ]);

        try {
            $this->configService->deactivate($request->group, $request->key);
            return $this->successResponse(null, 'Configuration deactivated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تفعيل إعداد.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'group' => 'required|string',
            'key'   => 'required|string',
        ]);

        try {
            $this->configService->activate($request->group, $request->key);
            return $this->successResponse(null, 'Configuration activated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * مسح الكاش.
     */
    public function clearCache()
    {
        $this->configService->clearCache();
        return $this->successResponse(null, 'Configuration cache cleared.');
    }
}