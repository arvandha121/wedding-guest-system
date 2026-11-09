<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\ChangeEventStatusRequest;
use App\Http\Requests\Event\ListEventsRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Services\EventService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class EventController extends Controller
{
    public function __construct(private readonly EventService $events)
    {
    }

    public function index(ListEventsRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);

        return ApiResponse::paginated(
            $this->events->paginate($request->user(), $request->validated()),
            EventResource::class,
            'Daftar acara berhasil diambil.',
        );
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        Gate::authorize('create', Event::class);

        $event = $this->events->create(
            $request->user(),
            $request->validated(),
            $request->file('banner'),
            $request->file('couple_photo'),
        );

        return ApiResponse::success($this->present($event, $request), 'Acara berhasil dibuat.', 201);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        return ApiResponse::success($this->present($event, $request), 'Detail acara berhasil diambil.');
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $event = $this->events->update(
            $event,
            $request->validated(),
            $request->file('banner'),
            $request->file('couple_photo'),
            $request->boolean('remove_banner'),
            $request->boolean('remove_couple_photo'),
        );

        return ApiResponse::success($this->present($event, $request), 'Acara berhasil diperbarui.');
    }

    public function status(ChangeEventStatusRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('changeStatus', $event);

        $event = $this->events->changeStatus($event, EventStatus::from($request->validated('status')));

        return ApiResponse::success($this->present($event, $request), 'Status acara berhasil diperbarui.');
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $this->events->archive($event);

        return ApiResponse::success(null, 'Acara berhasil diarsipkan.');
    }

    public function restore(Request $request, Event $trashedEvent): JsonResponse
    {
        Gate::authorize('restore', $trashedEvent);

        $event = $this->events->restore($trashedEvent);

        return ApiResponse::success($this->present($event, $request), 'Acara berhasil dipulihkan.');
    }

    private function present(Event $event, Request $request): array
    {
        return EventResource::make($event->load('sessions'))->resolve($request);
    }
}
