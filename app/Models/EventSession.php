<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSession extends Model
{
    use HasFactory, HasPublicUlid;

    protected $table = 'event_sessions';

    protected $fillable = ['name', 'start_time', 'end_time', 'checkin_opens_at', 'status', 'sort_order'];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'checkin_opens_at' => 'datetime',
            'status' => SessionStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
