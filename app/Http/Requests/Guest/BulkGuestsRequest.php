<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkGuestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['string', 'size:26'],
            // Untuk bulk-category: kunci "category" wajib ada; null = hapus kategori.
            'category' => $this->routeIs('*.bulk-category')
                ? ['present', 'nullable', 'string', Rule::exists('guest_categories', 'ulid')->where('event_id', $this->route('event')->id)]
                : ['prohibited'],
        ];
    }
}
