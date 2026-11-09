<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeUserStatusRequest;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seluruh route dibatasi middleware role:super_admin.
 */
final class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(ListUsersRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $users = User::query()
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $like = '%' . mb_strtolower(addcslashes($search, '%_\\')) . '%';
                $query->where(function ($inner) use ($like): void {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        return ApiResponse::paginated($users, UserResource::class, 'Daftar pengguna berhasil diambil.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated(), UserRole::from($request->validated('role')));

        return ApiResponse::success(
            UserResource::make($user)->resolve($request),
            'Pengguna berhasil dibuat.',
            201,
        );
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return ApiResponse::success(UserResource::make($user)->resolve($request), 'Detail pengguna berhasil diambil.');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->users->update($user, $request->validated());

        return ApiResponse::success(UserResource::make($user)->resolve($request), 'Pengguna berhasil diperbarui.');
    }

    public function status(ChangeUserStatusRequest $request, User $user): JsonResponse
    {
        $user = $this->users->setStatus($user, UserStatus::from($request->validated('status')));

        return ApiResponse::success(UserResource::make($user)->resolve($request), 'Status pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->users->delete($user);

        return ApiResponse::success(null, 'Pengguna berhasil dihapus.');
    }
}
