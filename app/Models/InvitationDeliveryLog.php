<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationDeliveryLog extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime',
            'failed_at' => 'datetime', 'attempt' => 'integer',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
