<?php

namespace App\Services\Auth;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Contracts\Repositories\SessionRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\AgentProfileRepositoryInterface;
use App\Contracts\Repositories\MerchantProfileRepositoryInterface;
use App\Traits\ConfigurableTrait;
use App\Traits\RateLimiterTrait;
use App\Traits\AuditableTrait;
use App\Traits\OtpVerificationTrait;
use App\Traits\SessionManagementTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestAuthService
{
    use ConfigurableTrait,
        RateLimiterTrait,
        AuditableTrait,
        OtpVerificationTrait,
        SessionManagementTrait;

    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected AgentProfileRepositoryInterface $agentProfileRepo,
        protected MerchantProfileRepositoryInterface $merchantProfileRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected CacheRepositoryInterface $cacheRepo,
        protected SessionRepositoryInterface $sessionRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة من الـ Traits -------------------

    protected function getCacheRepo(): CacheRepositoryInterface { return $this->cacheRepo; }
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }
    protected function getSessionRepo(): SessionRepositoryInterface { return $this->sessionRepo; }
    protected function getDefaultSessionExpiry(): int { return $this->getSessionExpiryMinutes(); }

    // تنفيذ دوال OtpVerificationTrait (يمكن جلب القيم من ConfigurableTrait)
    protected function getOtpTtlSeconds(): int { return 300; } // 5 دقائق
    protected function getOtpMaxResendAttempts(): int { return $this->getMaxVerificationAttempts(); }
    protected function getOtpResendWindowSeconds(): int { return $this->getVerificationLockoutSeconds(); }

    // دوال القيود (بعضها مستخدم مباشرة من ConfigurableTrait، لكننا سنعيد تعريفها للوضوح)
    protected function getMaxLoginAttempts(): int { return (int) $this->configRepo->getValue('security', 'login.max_attempts') ?? 5; }
    protected function getLoginLockoutSeconds(): int { return (int) $this->configRepo->getValue('security', 'login.lockout_seconds') ?? 300; }
    protected function getMaxRegistrationAttempts(): int { return (int) $this->configRepo->getValue('security', 'registration.max_attempts') ?? 3; }
    protected function getRegistrationLockoutSeconds(): int { return (int) $this->configRepo->getValue('security', 'registration.lockout_seconds') ?? 600; }
    protected function getMaxResetPasswordAttempts(): int { return (int) $this->configRepo->getValue('security', 'reset_password.max_attempts') ?? 3; }
    protected function getResetPasswordLockoutSeconds(): int { return (int) $this->configRepo->getValue('security', 'reset_password.lockout_seconds') ?? 900; }
    protected function getMaxVerificationAttempts(): int { return (int) $this->configRepo->getValue('security', 'verification.max_attempts') ?? 5; }
    protected function getVerificationLockoutSeconds(): int { return (int) $this->configRepo->getValue('security', 'verification.lockout_seconds') ?? 3600; }

    // ------------------- عمليات التسجيل -------------------

    public function register(array $data): array
    {
        $email = $data['email'];
        $phone = $data['phone'];
        $emailKey = "register_attempts:email:" . md5($email);
        $phoneKey = "register_attempts:phone:" . md5($phone);

        $this->checkRateLimit($emailKey, $this->getMaxRegistrationAttempts(), 'Too many registration attempts.');
        $this->checkRateLimit($phoneKey, $this->getMaxRegistrationAttempts(), 'Too many registration attempts.');

        try {
            return DB::transaction(function () use ($data, $emailKey, $phoneKey) {
                if ($this->userRepo->existsByEmail($data['email'])) {
                    throw ValidationException::withMessages(['email' => 'Email already taken.']);
                }
                if ($this->userRepo->existsByPhone($data['phone'])) {
                    throw ValidationException::withMessages(['phone' => 'Phone already taken.']);
                }

                $data['password'] = Hash::make($data['password']);
                $data['uuid'] = (string) Str::uuid();
                $data['is_verified'] = false;

                $user = $this->userRepo->create($data);
                $this->walletRepo->create($user->id, ['currency' => 'USD', 'status' => 'active']);

                if ($user->user_type === 'agent') {
                    $this->agentProfileRepo->create($user->id, [
                        'commission_type' => 'percentage',
                        'commission_value' => 0,
                        'is_active' => true,
                    ]);
                } elseif ($user->user_type === 'merchant') {
                    $this->merchantProfileRepo->create($user->id, [
                        'business_name' => $data['name'] ?? '',
                        'is_active' => true,
                    ]);
                }

                $this->logAudit('register', 'user', $user->id, $user->id, null, $user->toArray());
                $this->resetAttempts($emailKey);
                $this->resetAttempts($phoneKey);

                return $user->toArray();
            });
        } catch (\Exception $e) {
            $this->recordFailedAttempt($emailKey, $this->getRegistrationLockoutSeconds());
            $this->recordFailedAttempt($phoneKey, $this->getRegistrationLockoutSeconds());
            throw $e;
        }
    }

    // ------------------- تسجيل الدخول -------------------

    public function login(string $login, string $password, string $deviceInfo = '', ?array $location = null): array
    {
        $attemptKey = "login_attempts:" . md5($login);
        $this->checkRateLimit($attemptKey, $this->getMaxLoginAttempts(), 'Too many login attempts.');

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = $field === 'email' ? $this->userRepo->getByEmail($login) : $this->userRepo->getByPhone($login);

        if (!$user) {
            $this->recordFailedLoginAttempt($attemptKey);
            throw ValidationException::withMessages(['login' => 'Invalid credentials.']);
        }

        if ($user->status === 'suspended') {
            throw ValidationException::withMessages(['login' => 'Your account is suspended.']);
        }
        if ($user->status === 'deleted') {
            throw ValidationException::withMessages(['login' => 'Your account has been deleted.']);
        }
        if (!Hash::check($password, $user->password)) {
            $this->recordFailedLoginAttempt($attemptKey);
            throw ValidationException::withMessages(['login' => 'Invalid credentials.']);
        }

        $this->resetAttempts($attemptKey);
        $session = $this->createNewSession($user->id, ['user_agent' => $deviceInfo ?: request()->userAgent()], $location);
        $this->logAudit('login', 'user', $user->id, $user->id, null, ['session_id' => $session['session_id']]);

        return [
            'user' => $user->toArray(),
            'session' => [
                'token' => $session['token'],
                'expires_at' => $session['expires_at'],
            ],
        ];
    }

    protected function recordFailedLoginAttempt(string $key): void
    {
        $this->recordFailedAttempt($key, $this->getLoginLockoutSeconds());
    }

    // ------------------- إعادة تعيين كلمة المرور -------------------

    public function requestPasswordReset(string $emailOrPhone): string
    {
        $key = "reset_request_attempts:" . md5($emailOrPhone);
        $this->checkRateLimit($key, $this->getMaxResetPasswordAttempts(), 'Too many reset requests.');

        $field = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = $field === 'email' ? $this->userRepo->getByEmail($emailOrPhone) : $this->userRepo->getByPhone($emailOrPhone);

        if (!$user) {
            $this->recordFailedAttempt($key, $this->getResetPasswordLockoutSeconds());
            throw ValidationException::withMessages(['reset' => 'If the account exists, a reset code has been sent.']);
        }

        $otpKey = "password_reset:user:" . $user->id;
        $code = $this->generateOtpCode(6);
        $this->storeOtpCode($otpKey, $code, $this->getOtpTtlSeconds());
        $this->resetAttempts($key); // success

        return $code;
    }

    public function resetPassword(string $emailOrPhone, string $code, string $newPassword): bool
    {
        $field = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = $field === 'email' ? $this->userRepo->getByEmail($emailOrPhone) : $this->userRepo->getByPhone($emailOrPhone);
        if (!$user) {
            throw ValidationException::withMessages(['reset' => 'Invalid request.']);
        }

        $attemptKey = "reset_attempts:user:" . $user->id;
        $this->checkRateLimit($attemptKey, $this->getMaxResetPasswordAttempts(), 'Too many invalid reset attempts.');

        $otpKey = "password_reset:user:" . $user->id;
        try {
            $this->verifyOtpCode($otpKey, $code);
        } catch (ValidationException $e) {
            $this->recordFailedAttempt($attemptKey, $this->getResetPasswordLockoutSeconds());
            throw $e;
        }

        $this->resetAttempts($attemptKey);
        $updated = $this->userRepo->update($user->id, ['password' => Hash::make($newPassword)]);

        if ($updated) {
            $this->sessionRepo->deleteAllByUserId($user->id);
            $this->logAudit('password_reset', 'user', $user->id, $user->id, null, null);
        }
        return $updated;
    }

    // ------------------- تأكيد البريد الإلكتروني -------------------

    public function sendEmailVerification(int $userId): void
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->is_verified) return;

        $key = "verification_request:user:" . $userId;
        $this->checkRateLimit($key, $this->getMaxVerificationAttempts(), 'Too many verification requests.');

        $otpKey = "email_verification:{$userId}";
        $code = $this->generateOtpCode(6);
        $this->storeOtpCode($otpKey, $code, $this->getOtpTtlSeconds());
        $this->recordFailedAttempt($key, $this->getVerificationLockoutSeconds()); // record request
        // هنا يمكن إرسال الكود عبر البريد الإلكتروني (حدث)
    }

    public function verifyEmail(int $userId, string $code): bool
    {
        $attemptKey = "verify_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxVerificationAttempts(), 'Too many invalid verification attempts.');

        $otpKey = "email_verification:{$userId}";
        try {
            $this->verifyOtpCode($otpKey, $code);
        } catch (ValidationException $e) {
            $this->recordFailedAttempt($attemptKey, $this->getVerificationLockoutSeconds());
            throw $e;
        }

        $updated = $this->userRepo->update($userId, ['is_verified' => true]);
        if ($updated) {
            $this->resetAttempts($attemptKey);
            $this->logAudit('email_verified', 'user', $userId, $userId, null, null);
        }
        return $updated;
    }
}