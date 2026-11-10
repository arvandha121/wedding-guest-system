<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exports\GuestsExport;
use App\Exports\GuestTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\BulkGuestsRequest;
use App\Http\Requests\Guest\ImportGuestsRequest;
use App\Http\Requests\Guest\ListGuestsRequest;
use App\Http\Requests\Guest\StoreGuestRequest;
use App\Http\Requests\Guest\UpdateGuestRequest;
use App\Http\Resources\Api\V1\GuestResource;
use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestImportService;
use App\Services\GuestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class GuestController extends Controller
{
    public function __construct(
        private readonly GuestService $guests,
        private readonly GuestImportService $imports,
    ) {
    }

    public function index(ListGuestsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        return ApiResponse::paginated(
            $this->guests->paginate($event, $request->validated()),
            GuestResource::class,
            'Daftar tamu berhasil diambil.',
        );
    }

    public function store(StoreGuestRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        ['guest' => $guest, 'duplicates' => $duplicates] = $this->guests->create($event, $request->validated());

        $meta = $duplicates->isEmpty() ? [] : [
            'duplicates' => $duplicates->map(fn (Guest $g) => ['id' => $g->ulid, 'name' => $g->name])->all(),
            'warning' => 'Tamu dengan nama dan telepon yang sama sudah ada.',
        ];

        return ApiResponse::success(GuestResource::make($guest)->resolve($request), 'Tamu berhasil ditambahkan.', 201, $meta);
    }

    public function show(Request $request, Guest $guest): JsonResponse
    {
        Gate::authorize('manageGuests', $guest->event);

        return ApiResponse::success(GuestResource::make($guest->load('category'))->resolve($request), 'Detail tamu berhasil diambil.');
    }

    public function update(UpdateGuestRequest $request, Guest $guest): JsonResponse
    {
        Gate::authorize('manageGuests', $guest->event);

        return ApiResponse::success(
            GuestResource::make($this->guests->update($guest, $request->validated()))->resolve($request),
            'Tamu berhasil diperbarui.',
        );
    }

    public function destroy(Guest $guest): JsonResponse
    {
        Gate::authorize('manageGuests', $guest->event);

        $this->guests->delete($guest);

        return ApiResponse::success(null, 'Tamu berhasil dihapus.');
    }

    public function bulkDelete(BulkGuestsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        $count = $this->guests->bulkDelete($event, $request->validated('ids'));

        return ApiResponse::success(['affected' => $count], "{$count} tamu berhasil dihapus.");
    }

    public function bulkCategory(BulkGuestsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        $count = $this->guests->bulkCategory($event, $request->validated('ids'), $request->validated('category'));

        return ApiResponse::success(['affected' => $count], "Kategori {$count} tamu berhasil diubah.");
    }

    public function import(ImportGuestsRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        $report = $this->imports->import($event, $request->file('file'), $request->boolean('dry_run'));

        return ApiResponse::success(
            $report,
            $report['dry_run'] ? 'Pratinjau import selesai. Belum ada data yang disimpan.' : "{$report['imported']} tamu berhasil diimpor.",
        );
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
}
