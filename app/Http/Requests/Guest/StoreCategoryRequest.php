<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('guest_categories', 'name')
                    ->where('event_id', $category?->event_id ?? $this->route('event')->id)
                    ->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama kategori sudah dipakai pada acara ini.'];
    }
}
