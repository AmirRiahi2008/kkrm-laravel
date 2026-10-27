<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Requests\IndexRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ContentPresenter;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class UserController
{
    public function index(IndexRequest $request, ContentPresenter $present): JsonResponse
    {
        return ApiResponse::paginated(User::latest('id')->paginate($request->validated('per_page', 15)), fn (User $u) => $present->one($u, true));
    }

    public function show(User $user, ContentPresenter $present): JsonResponse
    {
        return ApiResponse::success($present->one($user, true));
    }

    public function store(Request $request, AuditService $audit, ContentPresenter $present): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data['email'] = strtolower($data['email']);
        $user = User::create($data);
        $audit->record('user.created', $user, ['role' => $user->role->value]);

        return ApiResponse::success($present->one($user, true), 201);
    }

    public function update(Request $request, User $user, AuditService $audit, ContentPresenter $present): JsonResponse
    {
        $data = $request->validate($this->rules($user));
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }
        $updated = DB::transaction(function () use ($user, $data, $request): User {
            $admins = User::where('role', UserRole::SuperAdmin->value)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $role = $data['role'] ?? $locked->role->value;
            $active = $data['is_active'] ?? $locked->is_active;
            $losingAdmin = $role !== UserRole::SuperAdmin->value || ! $active;
            if ($locked->role === UserRole::SuperAdmin && $locked->is_active && $losingAdmin && ($admins->count() <= 1 || $locked->id === $request->user()->id)) {
                throw ValidationException::withMessages(['role' => 'مدیر ارشد نمی‌تواند آخرین مدیر را غیرفعال کند یا دسترسی خودش را کاهش دهد.']);
            }
            $locked->fill($data);
            if ($locked->isDirty(['role', 'is_active', 'password', 'email'])) {
                $locked->session_version++;
                DB::table('sessions')->where('user_id', $locked->id)->delete();
            }
            $locked->save();

            return $locked;
        });
        $audit->record('user.updated', $updated, ['fields' => array_keys($data)]);

        return ApiResponse::success($present->one($updated, true));
    }

    private function rules(?User $user = null): array
    {
        $required = $user ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:150'],
            'email' => [$required, 'email', 'max:200', Rule::unique('users')->ignore($user?->id)],
            'password' => [$required, 'string', 'max:200', 'confirmed', Password::defaults()],
            'role' => [$required, Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
