<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserManagementResource;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeAction();

        $users = User::query()
            ->with(['investor'])
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Users retrieved successfully.',
            'data' => UserManagementResource::collection($users),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorizeAction();

        $user->load('investor');

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => new UserManagementResource($user),
        ]);
    }


    public function store(Request $request): JsonResponse
{
    try {
        $this->authorizeAction();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
            'investor_id' => ['nullable', 'exists:investors,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $roles = $validated['roles'] ?? [];
        $permissions = $validated['permissions'] ?? [];

        $this->ensureRolesExist($roles);
        $this->ensurePermissionsExist($permissions);

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'is_active' => $validated['is_active'] ?? true,
            'investor_id' => $validated['investor_id'] ?? null,
        ]);

        if (!empty($roles)) {
            $user->syncRoles($roles);
        }

        if (!empty($permissions)) {
            $user->syncPermissions($permissions);
        }

        $user->load('investor');

        return response()->json([
            'message' => 'User created successfully.',
            'data' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => (bool) $user->is_active,
                'investor_id' => $user->investor_id,
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getDirectPermissions()->pluck('name')->values(),
                'all_permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ], 201);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Create user failed.',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace_hint' => class_basename($e),
        ], 500);
    }
}


    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorizeAction();

        $validated = $request->validated();

        $roles = $validated['roles'] ?? null;
        $permissions = $validated['permissions'] ?? null;

        if (is_array($roles)) {
            $this->ensureRolesExist($roles);
        }

        if (is_array($permissions)) {
            $this->ensurePermissionsExist($permissions);
        }

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => $validated['phone'] ?? $user->phone,
            'password' => $validated['password'] ?? $user->password,
            'is_active' => $validated['is_active'] ?? $user->is_active,
            'investor_id' => array_key_exists('investor_id', $validated)
                ? $validated['investor_id']
                : $user->investor_id,
        ]);

        if (is_array($roles)) {
            $user->syncRoles($roles);
        }

        if (is_array($permissions)) {
            $user->syncPermissions($permissions);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserManagementResource($user->fresh('investor')),
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $this->authorizeAction();

        if ((int) auth()->id() === (int) $user->id && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages([
                'is_active' => ['You cannot deactivate your own account.'],
            ]);
        }

        $user->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'message' => 'User status updated successfully.',
            'data' => new UserManagementResource($user->fresh('investor')),
        ]);
    }

    protected function authorizeAction(): void
    {
        abort_unless(auth()->user()?->can('manage users'), 403, 'You are not authorized to manage users.');
    }

    protected function ensureRolesExist(array $roles): void
    {
        $count = Role::query()->whereIn('name', $roles)->count();

        if ($count !== count($roles)) {
            throw ValidationException::withMessages([
                'roles' => ['One or more selected roles do not exist.'],
            ]);
        }
    }

    protected function ensurePermissionsExist(array $permissions): void
    {
        $count = Permission::query()->whereIn('name', $permissions)->count();

        if ($count !== count($permissions)) {
            throw ValidationException::withMessages([
                'permissions' => ['One or more selected permissions do not exist.'],
            ]);
        }
    }
}