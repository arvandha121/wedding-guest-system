<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi lewat EventPolicy::manageGuests di controller
    }

    protected function eventId(): ?int
    {
        return $this->route('event')?->id ?? $this->route('guest')?->event_id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'family_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,25}$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:500'],
            'organization' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'quota' => ['nullable', 'integer', 'between:1,100'],
            'category' => ['nullable', 'string', Rule::exists('guest_categories', 'ulid')->where('event_id', $this->eventId())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tamu wajib diisi.',
            'phone.regex' => 'Nomor telepon tidak valid.',
            'quota.between' => 'Kuota harus antara 1 dan 100.',
            'category.exists' => 'Kategori tidak ditemukan pada acara ini.',
        ];
    }
}
