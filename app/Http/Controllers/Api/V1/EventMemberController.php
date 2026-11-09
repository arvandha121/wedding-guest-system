<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\AddMemberRequest;
use App\Http\Requests\Member\StoreReceptionistRequest;
use App\Http\Resources\Api\V1\EventMemberResource;
use App\Models\Event;
use App\Models\User;
use App\Services\EventMemberService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class EventMemberController extends Controller
{
    public function __construct(private readonly EventMemberService $members) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('viewMembers', $event);

        $filters = $request->validate([
            'role' => ['nullable', Rule::in([UserRole::EventAdmin->value, UserRole::Receptionist->value])],
        ]);

        $query = $event->members();

        if (filled($filters['role'] ?? null)) {
            $query->wherePivot('assigned_role', $filters['role']);
        }

        $members = $query->orderBy('users.name')->get();

        return ApiResponse::success(
            EventMemberResource::collection($members)->resolve($request),
            'Daftar penugasan berhasil diambil.',
        );
    }

    public function store(AddMemberRequest $request, Event $event): JsonResponse
    {
        $target = User::query()->where('ulid', $request->validated('user'))->firstOrFail();

        Gate::authorize('assignMember', [$event, $target]);

        $this->members->assign($event, $target);

        return ApiResponse::success(null, 'Pengguna berhasil ditugaskan.', 201);
    }

    public function destroy(Event $event, User $user): JsonResponse
    {
        Gate::authorize('assignMember', [$event, $user]);

        $this->members->remove($event, $user);

        return ApiResponse::success(null, 'Penugasan berhasil dicabut.');
    }

    public function storeReceptionist(StoreReceptionistRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageReceptionists', $event);

        $user = $this->members->createReceptionist($event, $request->validated());

        return ApiResponse::success(
            [
                'id' => $user->ulid,
                'name' => $user->name,
                'email' => $user->email,
                'assigned_role' => UserRole::Receptionist->value,
            ],
            'Akun resepsionis berhasil dibuat dan ditugaskan.',
            201,
        );
    }
}
