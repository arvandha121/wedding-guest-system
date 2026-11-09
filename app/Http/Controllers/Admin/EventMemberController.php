<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\AddMemberRequest;
use App\Http\Requests\Member\StoreReceptionistRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\EventMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class EventMemberController extends Controller
{
    public function __construct(private readonly EventMemberService $members)
    {
    }

    public function store(AddMemberRequest $request, Event $event): RedirectResponse
    {
        $target = User::query()->where('ulid', $request->validated('user'))->firstOrFail();

        Gate::authorize('assignMember', [$event, $target]);

        $this->members->assign($event, $target);

        return redirect()->route('admin.events.show', $event)->with('status', 'Pengguna berhasil ditugaskan.');
    }

    public function destroy(Event $event, User $user): RedirectResponse
    {
        Gate::authorize('assignMember', [$event, $user]);

        $this->members->remove($event, $user);

        return redirect()->route('admin.events.show', $event)->with('status', 'Penugasan berhasil dicabut.');
    }

    public function storeReceptionist(StoreReceptionistRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageReceptionists', $event);

        $this->members->createReceptionist($event, $request->validated());

        return redirect()->route('admin.events.show', $event)
            ->with('status', 'Akun resepsionis berhasil dibuat dan ditugaskan.');
    }
}
