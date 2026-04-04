<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AccessControlController extends Controller
{
    public function roles(): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage users'), 403, 'You are not authorized to view roles.');

        return response()->json([
            'message' => 'Roles retrieved successfully.',
            'data' => Role::query()
                ->orderBy('name')
                ->pluck('name')
                ->values(),
        ]);
    }

    public function permissions(): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage users'), 403, 'You are not authorized to view permissions.');

        return response()->json([
            'message' => 'Permissions retrieved successfully.',
            'data' => Permission::query()
                ->orderBy('name')
                ->pluck('name')
                ->values(),
        ]);
    }
}