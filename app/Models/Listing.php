<?php

namespace App\Models;

use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'category_id',
    'entrepreneur_profile_id',
    'title',
    'description',
    'price',
    'listing_type',
    'availability_type',
    'status',
    'condition',
    'stock',
    'campus_location',
    'is_active',
    'published_at',
])]
class Listing extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_EXCHANGE = 'exchange';

    public const TYPES = [self::TYPE_SALE, self::TYPE_EXCHANGE];

    public const AVAILABILITY_DAILY = 'daily';

    public const AVAILABILITY_UNIQUE = 'unique';

    public const AVAILABILITY_TYPES = [self::AVAILABILITY_DAILY, self::AVAILABILITY_UNIQUE];

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD_OUT_TODAY = 'sold_out_today';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_SOLD = 'sold';

    public const STATUS_EXCHANGED = 'exchanged';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_SOLD_OUT_TODAY,
        self::STATUS_RESERVED,
        self::STATUS_SOLD,
        self::STATUS_EXCHANGED,
    ];

    public const CONDITION_NEW = 'new';

    public const CONDITION_USED = 'used';

    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function entrepreneurProfile(): BelongsTo
    {
        return $this->belongsTo(EntrepreneurProfile::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withPivot('created_at');
    }

    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->status === self::STATUS_AVAILABLE
            && ($this->stock === null || $this->stock > 0);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
