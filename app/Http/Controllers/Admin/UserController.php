<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeUserStatusRequest;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserWebRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Seluruh route dibatasi middleware role:super_admin.
 */
final class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(ListUsersRequest $request): View
    {
        $filters = $request->validated();

        $users = User::query()
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $like = '%' . mb_strtolower(addcslashes($search, '%_\\')) . '%';
                $query->where(function ($inner) use ($like): void {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated(), UserRole::from($request->validated('role')));

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserWebRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function status(ChangeUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->users->setStatus($user, UserStatus::from($request->validated('status')));

        return redirect()->route('admin.users.index')->with('status', 'Status pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->users->delete($user);

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil dihapus.');
    }
}
