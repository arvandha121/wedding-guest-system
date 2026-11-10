<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StoreCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Event;
use App\Models\GuestCategory;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories)
    {
    }

    public function index(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        $items = GuestCategory::query()->where('event_id', $event->id)->withCount('guests')->orderBy('name')->get();

        return ApiResponse::success(CategoryResource::collection($items)->resolve($request), 'Daftar kategori berhasil diambil.');
    }

    public function store(StoreCategoryRequest $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        return ApiResponse::success(
            CategoryResource::make($this->categories->create($event, $request->validated()))->resolve($request),
            'Kategori berhasil dibuat.',
            201,
        );
    }

    public function update(StoreCategoryRequest $request, GuestCategory $category): JsonResponse
    {
        Gate::authorize('manageGuests', $category->event);

        return ApiResponse::success(
            CategoryResource::make($this->categories->update($category, $request->validated()))->resolve($request),
            'Kategori berhasil diperbarui.',
        );
    }

    public function destroy(GuestCategory $category): JsonResponse
    {
        Gate::authorize('manageGuests', $category->event);

        $this->categories->delete($category);

        return ApiResponse::success(null, 'Kategori berhasil dihapus.');
    }
}
