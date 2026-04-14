<?php

namespace App\Filament\Pages;


use Filament\Pages\Auth\Login;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use App\Services\Auth\GuestAuthService;
use Illuminate\Support\Facades\Auth;

class CustomLogin extends Login
{
    /**
     * تجاوز طريقة المصادقة لاستخدام GuestAuthService.
     */
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        try {
            // استخدام خدمة المصادقة المخصصة
            $result = app(GuestAuthService::class)->login(
                login: $data['email'], // في نموذج Filament الافتراضي الحقل اسمه 'email'
                password: $data['password'],
                deviceInfo: request()->userAgent() ?? 'Filament Panel',
                location: ['ip' => request()->ip()]
            );

            // تسجيل دخول المستخدم في جلسة Laravel لكي تعمل Filament
            Auth::loginUsingId($result['user']['id'], $data['remember'] ?? false);

            // تخزين التوكن في الجلسة لاستخدامه لاحقًا مع SessionService
            session(['auth_token' => $result['session']['token']]);

            session()->regenerate();

            return app(LoginResponse::class);
        } catch (ValidationException $e) {
            // إعادة رمي الاستثناءات مع الرسائل المناسبة للنموذج
            throw ValidationException::withMessages([
                'data.email' => $e->getMessage(),
            ]);
        }
    }

    /**
     * يمكن تجاوز هذه الدالة لتغيير حقل "البريد الإلكتروني" إلى "رقم الهاتف أو البريد"
     * ولكن سنتركها كما هي لاستخدام الحقل الافتراضي 'email'.
     * إذا أردت تخصيصها، انظر التعليق أدناه.
     */
    /*
    protected function getEmailFormComponent(): \Filament\Forms\Components\Component
    {
        return \Filament\Forms\Components\TextInput::make('email')
            ->label('رقم الهاتف أو البريد الإلكتروني')
            ->required()
            ->autocomplete()
            ->autofocus();
    }
    */
}