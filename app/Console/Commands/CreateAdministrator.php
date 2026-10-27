<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class CreateAdministrator extends Command
{
    protected $signature = 'app:create-admin {email} {--name=مدیر روابط عمومی}';

    protected $description = 'Create an internal administrator with a hidden password prompt';

    public function handle(): int
    {
        $password = $this->secret('Password');
        $confirm = $this->secret('Confirm password');
        $data = [
            'name' => $this->option('name'), 'email' => strtolower($this->argument('email')),
            'password' => $password, 'password_confirmation' => $confirm,
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:200', 'unique:users'],
            'password' => ['required', 'string', 'max:200', 'confirmed', Password::defaults()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        unset($data['password_confirmation']);
        User::create($data + ['role' => UserRole::SuperAdmin, 'is_active' => true]);
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
