<?php

namespace App\Models;

use Database\Factories\EntrepreneurProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'business_name',
    'slug',
    'description',
    'logo_path',
    'campus_location',
    'is_active',
])]
class EntrepreneurProfile extends Model
{
    /** @use HasFactory<EntrepreneurProfileFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entrepreneur_favorites')->withPivot('created_at');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
