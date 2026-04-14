<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use App\Services\Auth\GuestAuthService;

class CustomRegister extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.custom-register';
    protected static ?string $slug = 'register';

    public ?array $data = [];

    public function mount(): void
    {
        if (auth()->check()) {
            redirect()->intended('/admin');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحساب الأساسية')
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->placeholder('محمد أحمد')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('example@domain.com')
                            ->email()
                            ->required()
                            ->unique('users', 'email'),
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->placeholder('77XXXXXXX')
                            ->tel()
                            ->required()
                            ->unique('users', 'phone')
                            ->regex('/^[0-9]{7,15}$/')
                            ->helperText('أدخل رقم هاتف صحيح بدون رمز الدولة.'),
                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->placeholder('********')
                            ->password()
                            ->required()
                            ->minLength(6)
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('تأكيد كلمة المرور')
                            ->placeholder('********')
                            ->password()
                            ->required(),
                        Select::make('user_type')
                            ->label('نوع الحساب')
                            ->options([
                                'customer' => 'عميل',
                                'agent'    => 'وكيل',
                                'merchant' => 'تاجر',
                            ])
                            ->required()
                            ->reactive()
                            ->default('customer'),
                    ])
                    ->columns(2),

                Section::make('معلومات إضافية (للوكلاء والتجار فقط)')
                    ->schema([
                        TextInput::make('business_name')
                            ->label('اسم النشاط التجاري')
                            ->placeholder('متجر الإلكترونيات الحديثة')
                            ->visible(fn (callable $get) => $get('user_type') === 'merchant')
                            ->requiredIf('user_type', 'merchant'),
                        Select::make('business_type')
                            ->label('نوع النشاط التجاري')
                            ->options([
                                'retail'    => 'تجزئة',
                                'wholesale' => 'جملة',
                                'service'   => 'خدمات',
                                'restaurant'=> 'مطعم',
                                'other'     => 'أخرى',
                            ])
                            ->visible(fn (callable $get) => $get('user_type') === 'merchant')
                            ->requiredIf('user_type', 'merchant'),
                    ])
                    ->visible(fn (callable $get) => in_array($get('user_type'), ['agent', 'merchant'])),
            ])
            ->statePath('data');
    }

    public function register(): void
    {
        $data = $this->form->getState();

        try {
            $user = app(GuestAuthService::class)->register([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'phone'         => $data['phone'],
                'password'      => $data['password'],
                'user_type'     => $data['user_type'],
                'business_name' => $data['business_name'] ?? null,
                'business_type' => $data['business_type'] ?? null,
            ]);

            Notification::make()
                ->title('تم إنشاء الحساب بنجاح!')
                ->body('يمكنك الآن تسجيل الدخول باستخدام بريدك الإلكتروني أو رقم هاتفك.')
                ->success()
                ->send();

            redirect()->route('filament.admin.auth.login');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ غير متوقع')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction(),
        ];
    }

    protected function getRegisterFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('register')
            ->label('إنشاء حساب')
            ->submit('register');
    }

    public function hasLogo(): bool
    {
        return true;
    }
}