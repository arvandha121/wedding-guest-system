<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckinMode;
use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory, HasPublicUlid, SoftDeletes;

    /** status, created_by, dan path gambar diatur lewat service, bukan mass assignment. */
    protected $fillable = [
        'name', 'title', 'groom_name', 'bride_name', 'groom_family', 'bride_family',
        'description', 'event_date', 'start_time', 'end_time', 'venue_name', 'venue_address',
        'google_maps_url', 'theme', 'checkin_mode', 'checkin_opens_at', 'checkin_closes_at',
        'allow_manual_checkin', 'contact_name', 'contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'status' => EventStatus::class,
            'checkin_mode' => CheckinMode::class,
            'checkin_opens_at' => 'datetime',
            'checkin_closes_at' => 'datetime',
            'allow_manual_checkin' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('sort_order')->orderBy('start_time');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')
            ->withPivot('assigned_role')
            ->withTimestamps();
    }

    /**
     * Acara yang boleh dilihat pengguna: Super Admin semuanya, selain itu hanya yang ditugaskan.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereIn('events.id', function ($sub) use ($user): void {
            $sub->select('event_id')->from('event_user')->where('user_id', $user->id);
        });
    }

    public function hasMember(User $user, ?UserRole $role = null): bool
    {
        $query = $this->members()->where('users.id', $user->id);

        if ($role !== null) {
            $query->wherePivot('assigned_role', $role->value);
        }

        return $query->exists();
    }

    public function bannerUrl(): ?string
    {
        return $this->banner_path ? Storage::disk('public')->url($this->banner_path) : null;
    }

    public function couplePhotoUrl(): ?string
    {
        return $this->couple_photo_path ? Storage::disk('public')->url($this->couple_photo_path) : null;
    }
}
