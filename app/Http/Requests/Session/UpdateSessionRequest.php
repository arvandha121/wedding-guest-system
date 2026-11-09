<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use App\Enums\SessionStatus;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends StoreSessionRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'status' => ['nullable', Rule::enum(SessionStatus::class)],
            'sort_order' => ['nullable', 'integer', 'between:0,250'],
        ];
    }
}
