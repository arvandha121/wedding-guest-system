<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;

/** Versi panel web: peran boleh diganti (API tetap melarang lewat UpdateUserRequest). */
class UpdateUserWebRequest extends UpdateUserRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'role' => ['required', Rule::in([UserRole::EventAdmin->value, UserRole::Receptionist->value])],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'role.required' => 'Pilih peran pengguna.',
            'role.in' => 'Peran tidak valid.',
        ];
    }
}
