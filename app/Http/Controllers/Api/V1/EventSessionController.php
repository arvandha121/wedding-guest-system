<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Http\Resources\Api\V1\EventSessionResource;
use App\Models\Event;
use App\Models\EventSession;
use App\Services\SessionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class EventSessionController extends Controller
{
    public function __construct(private readonly SessionService $sessions)
    {
    }

    public function index(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        return ApiResponse::success(
            EventSessionResource::collection($event->sessions()->get())->resolve($request),
            'Daftar sesi berhasil diambil.',
        );
    }

    public function store(StoreSessionRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageSessions', $event);

        $session = $this->sessions->create($event, $request->validated());

        return ApiResponse::success(
            EventSessionResource::make($session)->resolve($request),
            'Sesi berhasil dibuat.',
            201,
        );
    }

    public function update(UpdateSessionRequest $request, EventSession $eventSession): JsonResponse
    {
        Gate::authorize('manageSessions', $eventSession->event);

        $session = $this->sessions->update($eventSession, $request->validated());

        return ApiResponse::success(
            EventSessionResource::make($session)->resolve($request),
            'Sesi berhasil diperbarui.',
        );
    }

    public function destroy(EventSession $eventSession): JsonResponse
    {
        Gate::authorize('manageSessions', $eventSession->event);

        $this->sessions->delete($eventSession);

        return ApiResponse::success(null, 'Sesi berhasil dihapus.');
    }
}
