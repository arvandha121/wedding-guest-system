<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\GuestsExport;
use App\Exports\GuestTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\ImportGuestsRequest;
use App\Http\Requests\Guest\ListGuestsRequest;
use App\Http\Requests\Guest\StoreGuestRequest;
use App\Http\Requests\Guest\UpdateGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Services\GuestImportService;
use App\Services\GuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class GuestController extends Controller
{
    public function __construct(
        private readonly GuestService $guests,
        private readonly GuestImportService $imports,
    ) {
    }

    public function index(ListGuestsRequest $request, Event $event): View
    {
        Gate::authorize('manageGuests', $event);

        return view('admin.guests.index', [
            'event' => $event,
            'guests' => $this->guests->paginate($event, $request->validated()),
            'filters' => $request->validated(),
            'categories' => GuestCategory::query()->where('event_id', $event->id)->orderBy('name')->get(['ulid', 'name']),
        ]);
    }

    public function create(Event $event): View
    {
        Gate::authorize('manageGuests', $event);

        return view('admin.guests.form', $this->formData($event, new Guest()));
    }

    public function store(StoreGuestRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        $result = $this->guests->create($event, $request->validated());
        $message = 'Tamu berhasil ditambahkan.'
            . ($result['duplicates']->isEmpty() ? '' : ' Perhatian: sudah ada tamu dengan nama dan telepon yang sama.');

        return redirect()->route('admin.events.guests.index', $event)->with('status', $message);
    }

    public function edit(Guest $guest): View
    {
        Gate::authorize('manageGuests', $guest->event);

        return view('admin.guests.form', $this->formData($guest->event, $guest->load('category')));
    }

    public function update(UpdateGuestRequest $request, Guest $guest): RedirectResponse
    {
        Gate::authorize('manageGuests', $guest->event);

        $this->guests->update($guest, $request->validated());

        return redirect()->route('admin.events.guests.index', $guest->event)->with('status', 'Tamu berhasil diperbarui.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        Gate::authorize('manageGuests', $guest->event);

        $this->guests->delete($guest);

        return redirect()->route('admin.events.guests.index', $guest->event)->with('status', 'Tamu berhasil dihapus.');
    }

    public function import(ImportGuestsRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        $report = $this->imports->import($event, $request->file('file'), $request->boolean('dry_run'));

        return redirect()->route('admin.events.guests.index', $event)
            ->with('status', $report['dry_run']
                ? "Pratinjau: {$report['valid']} baris valid, " . count($report['errors']) . ' bermasalah. Belum ada data disimpan.'
                : "{$report['imported']} tamu berhasil diimpor.")
            ->with('import_report', $report);
    }

    public function template(Event $event): BinaryFileResponse
    {
        Gate::authorize('manageGuests', $event);

        return Excel::download(new GuestTemplateExport(), 'template-tamu.xlsx');
    }

    public function export(Event $event): BinaryFileResponse
    {
        Gate::authorize('manageGuests', $event);

        return Excel::download(new GuestsExport($event), 'tamu-' . Str::slug($event->name) . '.xlsx');
    }

    private function formData(Event $event, Guest $guest): array
    {
        return [
            'event' => $event,
            'guest' => $guest,
            'categories' => GuestCategory::query()->where('event_id', $event->id)->orderBy('name')->get(['ulid', 'name']),
        ];
    }
}
