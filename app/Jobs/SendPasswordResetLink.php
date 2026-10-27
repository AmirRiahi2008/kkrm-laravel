<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetLink implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $email) {}

    public function handle(): void
    {
        if (User::where('email', $this->email)->where('is_active', true)->exists()) {
            Password::sendResetLink(['email' => $this->email]);
        }
    }
}
