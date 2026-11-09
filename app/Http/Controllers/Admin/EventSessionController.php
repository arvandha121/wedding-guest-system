<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Models\Event;
use App\Models\EventSession;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class EventSessionController extends Controller
{
    public function __construct(private readonly SessionService $sessions)
    {
    }

    public function store(StoreSessionRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageSessions', $event);

        $this->sessions->create($event, $request->validated());

        return redirect()->route('admin.events.show', $event)->with('status', 'Sesi berhasil ditambahkan.');
    }

    public function update(UpdateSessionRequest $request, EventSession $eventSession): RedirectResponse
    {
        Gate::authorize('manageSessions', $eventSession->event);

        $this->sessions->update($eventSession, $request->validated());

        return redirect()->route('admin.events.show', $eventSession->event)->with('status', 'Sesi berhasil diperbarui.');
    }

    public function destroy(EventSession $eventSession): RedirectResponse
    {
        Gate::authorize('manageSessions', $eventSession->event);

        $event = $eventSession->event;
        $this->sessions->delete($eventSession);

        return redirect()->route('admin.events.show', $event)->with('status', 'Sesi berhasil dihapus.');
    }
}
