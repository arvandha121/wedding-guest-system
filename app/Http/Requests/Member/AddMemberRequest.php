<?php

declare(strict_types=1);

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user' => ['required', 'string', Rule::exists('users', 'ulid')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'user.required' => 'Pilih pengguna yang akan ditugaskan.',
            'user.exists' => 'Pengguna tidak ditemukan.',
        ];
    }
}
