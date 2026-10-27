<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ContentPresenter;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

final class AuthController
{
    public function login(LoginRequest $request, AuditService $audit, ContentPresenter $present): JsonResponse
    {
        $credentials = $request->validated();
        if (! Auth::guard('web')->attempt($credentials + ['is_active' => true])) {
            $audit->record('auth.login_failed');
            throw ValidationException::withMessages(['email' => 'ایمیل یا رمز عبور صحیح نیست.']);
        }
        $request->session()->regenerate();
        $request->session()->forget('password_hash_web');
        $user = Auth::guard('web')->user();
        $request->session()->put('auth.version', $user->session_version);
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.login', $user);

        return ApiResponse::success($present->one($user));
    }

    public function me(Request $request, ContentPresenter $present): JsonResponse
    {
        return ApiResponse::success($present->one($request->user()));
    }

    public function logout(Request $request, AuditService $audit): JsonResponse
    {
        $audit->record('auth.logout', $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(['message' => 'از حساب خارج شدید.']);
    }

    public function changePassword(Request $request, AuditService $audit): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password:web', 'max:200'],
            'password' => ['required', 'string', 'max:200', 'confirmed', PasswordRule::defaults()],
        ]);
        $user = $request->user();
        DB::transaction(function () use ($user, $data): void {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $locked->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60), 'session_version' => $locked->session_version + 1])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
        $audit->record('auth.password_changed', $user);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(['message' => 'رمز تغییر کرد. دوباره وارد شوید.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:200']]);
        $email = strtolower($data['email']);
        SendPasswordResetLink::dispatch($email);

        return ApiResponse::success(['message' => 'اگر حساب فعالی با این ایمیل وجود داشته باشد، لینک بازیابی ارسال می‌شود.']);
    }

    public function resetPassword(Request $request, AuditService $audit): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:200'],
            'token' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'max:200', 'confirmed', PasswordRule::defaults()],
        ]);
        $data['email'] = strtolower($data['email']);
        $status = Password::reset($data, function (User $user, string $password) use ($audit): void {
            abort_unless($user->is_active, 422);
            DB::transaction(function () use ($user, $password): void {
                $locked = User::lockForUpdate()->findOrFail($user->id);
                $locked->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'session_version' => $locked->session_version + 1])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            });
            $audit->record('auth.password_reset', $user);
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => 'لینک بازیابی معتبر نیست یا منقضی شده است.']);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(['message' => 'رمز با موفقیت بازیابی شد.']);
    }
}
