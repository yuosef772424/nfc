<?php

namespace App\Http\Controllers\Api;

use App\Services\System\AppConfigService;

class AppConfigController extends BaseApiController
{
    public function __construct(protected AppConfigService $configService) {}

    /**
     * الحصول على الإعدادات العامة للتطبيق (مثل إصدار التطبيق، روابط التواصل...)
     */
    public function publicConfig()
    {
        $publicGroups = ['app', 'contact', 'social'];
        $config = [];

        foreach ($publicGroups as $group) {
            $config[$group] = $this->configService->getGroup($group);
        }

        return $this->successResponse($config);
    }
}