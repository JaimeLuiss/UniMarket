<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reporter_id',
    'listing_id',
    'reported_user_id',
    'reason',
    'description',
    'status',
    'reviewed_by',
    'reviewed_at',
    'resolution_notes',
])]
class Report extends Model
{
    public const REASON_MISLEADING_PRODUCT = 'misleading_product';

    public const REASON_INCORRECT_INFORMATION = 'incorrect_information';

    public const REASON_PROHIBITED_PRODUCT = 'prohibited_product';

    public const REASON_INAPPROPRIATE_LISTING = 'inappropriate_listing';

    public const REASON_POSSIBLE_FRAUD = 'possible_fraud';

    public const REASON_SPAM = 'spam';

    public const REASON_OTHER = 'other';

    public const REASON_LABELS = [
        self::REASON_MISLEADING_PRODUCT => 'Producto engañoso',
        self::REASON_INCORRECT_INFORMATION => 'Información incorrecta',
        self::REASON_PROHIBITED_PRODUCT => 'Producto prohibido',
        self::REASON_INAPPROPRIATE_LISTING => 'Publicación inapropiada',
        self::REASON_POSSIBLE_FRAUD => 'Posible fraude',
        self::REASON_SPAM => 'Spam',
        self::REASON_OTHER => 'Otro',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWED,
        self::STATUS_RESOLVED,
        self::STATUS_REJECTED,
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }
}
