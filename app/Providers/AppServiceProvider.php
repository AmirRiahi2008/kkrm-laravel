<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AlbumItem;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Observers\ContentObserver;
use App\Policies\PostPolicy;
use App\Support\ContentCatalog;
use App\Support\Portal;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $publicOrigin = rtrim((string) config('kkrm.public_site_url'), '/');
        if (app()->environment('production') && ! app()->runningInConsole() && $publicOrigin !== '' && request()->header('X-KKRM-Public-Origin') === $publicOrigin) {
            $parts = parse_url($publicOrigin);
            if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || ! empty($parts['path'])) {
                throw new \LogicException('PUBLIC_SITE_URL must be a valid HTTPS origin.');
            }
            URL::forceRootUrl($publicOrigin);
            URL::forceScheme('https');
        }
        AliasLoader::getInstance()->alias('P', Portal::class);
        Sanctum::getAccessTokenFromRequestUsing(fn () => null);
        Model::preventSilentlyDiscardingAttributes();
        DB::prohibitDestructiveCommands(app()->environment('production'));
        Gate::policy(Post::class, PostPolicy::class);
        Gate::define('manage-content', fn (User $user) => $user->is_active && $user->role->managesContent());
        Gate::define('manage-users', fn (User $user) => $user->is_active && $user->role === UserRole::SuperAdmin);
        foreach ([ContactMessage::class, Post::class, Media::class, Setting::class, AlbumItem::class, ...array_values(ContentCatalog::MODELS)] as $model) {
            $model::observe(ContentObserver::class);
        }
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());
        ResetPassword::createUrlUsing(fn (User $user, string $token) => rtrim(config('kkrm.frontend_url'), '/').'/auth/reset-password?'.http_build_query(['token' => $token, 'email' => $user->email]));
        RateLimiter::for('public-api', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));
        RateLimiter::for('admin-api', fn (Request $r) => Limit::perMinute(120)->by((string) $r->user()?->id ?: $r->ip()));
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(20)->by($r->ip()),
            Limit::perMinute(5)->by(hash('sha256', strtolower((string) $r->input('email')).'|'.$r->ip())),
        ]);
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));
        RateLimiter::for('contact', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(30)->by($r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(15)->by((string) $r->user()?->id));
    }
}
