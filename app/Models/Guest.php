<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use App\Support\Normalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, HasPublicUlid, SoftDeletes;

    /** event_id dan category_id diatur lewat service. */
    protected $fillable = ['name', 'family_name', 'phone', 'email', 'address', 'organization', 'notes', 'quota'];

    protected static function booted(): void
    {
        static::saving(function (Guest $guest): void {
            $guest->name_normalized = Normalizer::name((string) $guest->name);
            $guest->phone_normalized = Normalizer::phone($guest->phone);
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GuestCategory::class, 'category_id');
    }
}
