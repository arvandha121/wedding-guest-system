<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\GuestCategory;
use Illuminate\Support\Arr;

final class CategoryService
{
    public function seedDefaults(Event $event): void
    {
        foreach (config('wgs.default_guest_categories') as $name) {
            $category = new GuestCategory(['name' => $name]);
            $category->event_id = $event->id;
            $category->is_default = true;
            $category->save();
        }
    }

    public function create(Event $event, array $data): GuestCategory
    {
        $category = new GuestCategory(Arr::only($data, ['name', 'description']));
        $category->event_id = $event->id;
        $category->save();

        return $category->refresh();
    }

    public function update(GuestCategory $category, array $data): GuestCategory
    {
        $category->fill(Arr::only($data, ['name', 'description']))->save();

        return $category;
    }

    public function delete(GuestCategory $category): void
    {
        if ($category->guests()->exists()) {
            throw new ApiException('Kategori masih dipakai oleh tamu. Pindahkan tamunya terlebih dahulu.', 'CATEGORY_IN_USE', 409);
        }

        $category->delete();
    }
}
