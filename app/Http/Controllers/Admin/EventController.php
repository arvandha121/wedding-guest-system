<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CheckinMode;
use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\ChangeEventStatusRequest;
use App\Http\Requests\Event\ListEventsRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class EventController extends Controller
{
    public function __construct(private readonly EventService $events)
    {
    }

    public function index(ListEventsRequest $request): View
    {
        Gate::authorize('viewAny', Event::class);

        return view('admin.events.index', [
            'events' => $this->events->paginate($request->user(), $request->validated()),
            'filters' => $request->validated(),
            'statuses' => EventStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Event::class);

        return view('admin.events.create', $this->formData(new Event()));
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $event = $this->events->create(
            $request->user(),
            $request->validated(),
            $request->file('banner'),
            $request->file('couple_photo'),
        );

        return redirect()->route('admin.events.show', $event)->with('status', 'Acara berhasil dibuat.');
    }

    public function show(Request $request, Event $event): View
    {
        Gate::authorize('view', $event);

        $actor = $request->user();
        $event->load('sessions');

        $members = $event->members()->orderBy('users.name')->get();
        $memberIds = $members->pluck('id');

        $assignable = fn (UserRole $role) => User::query()
            ->where('role', $role->value)
            ->where('status', UserStatus::Active->value)
            ->whereNotIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'ulid', 'name', 'email']);

        return view('admin.events.show', [
            'event' => $event,
            'admins' => $members->filter(fn (User $u) => $u->pivot->assigned_role === UserRole::EventAdmin->value)->values(),
            'receptionists' => $members->filter(fn (User $u) => $u->pivot->assigned_role === UserRole::Receptionist->value)->values(),
            'assignableAdmins' => $actor->isSuperAdmin() ? $assignable(UserRole::EventAdmin) : collect(),
            'assignableReceptionists' => $assignable(UserRole::Receptionist),
            'canUpdate' => Gate::forUser($actor)->allows('update', $event),
            'canDelete' => Gate::forUser($actor)->allows('delete', $event),
            'canAssignAdmins' => $actor->isSuperAdmin(),
        ]);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        return view('admin.events.edit', $this->formData($event));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
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

        return redirect()->route('admin.events.show', $event)->with('status', 'Acara berhasil diperbarui.');
    }

    public function status(ChangeEventStatusRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('changeStatus', $event);

        $this->events->changeStatus($event, EventStatus::from($request->validated('status')));

        return redirect()->route('admin.events.show', $event)->with('status', 'Status acara berhasil diperbarui.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $this->events->archive($event);

        return redirect()->route('admin.events.index')->with('status', 'Acara berhasil diarsipkan.');
    }

    private function formData(Event $event): array
    {
        return [
            'event' => $event,
            'themes' => config('wgs.themes'),
            'checkinModes' => CheckinMode::cases(),
        ];
    }
}
