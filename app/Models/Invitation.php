<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckinMode;
use App\Enums\InvitationStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    use HasPublicUlid;

    /** Semua atribut diatur eksplisit oleh InvitationService. */
    protected $guarded = ['*'];

    protected $hidden = ['public_token_hash', 'public_token_enc', 'qr_token_hash', 'qr_token_enc'];

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'checkin_mode' => CheckinMode::class,
            'quota' => 'integer',
            'qr_version' => 'integer',
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', InvitationStatus::Revoked->value);
    }

    public function isRevoked(): bool
    {
        return $this->status === InvitationStatus::Revoked;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(EventSession::class, 'invitation_sessions')
            ->withPivot('attended_count')
            ->withTimestamps()
            ->orderBy('event_sessions.sort_order')
            ->orderBy('event_sessions.start_time');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InvitationDeliveryLog::class)->latest('id');
    }
}
