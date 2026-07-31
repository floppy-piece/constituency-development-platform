<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstituencyRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'user_id',
        'mp_id',
        'ward_id',
        'raw_message',
        'content',
        'upload_file_path',
        'file_type',
        'urgency',
        'category',
        'primary_topic',
        'latitude',
        'longitude',
        'is_within_constituency',
        'similarity_hash',
        'similar_count',
        'cluster_ward_ids',
        'status',
        'confidence',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_within_constituency' => 'boolean',
        'confidence' => 'float',
        'similar_count' => 'integer',
        'cluster_ward_ids' => 'array',
    ];

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    public const CONFIDENCE_THRESHOLD = 0.70;

    public static function statusFromConfidence(float $confidence): string
    {
        return $confidence < self::CONFIDENCE_THRESHOLD
            ? self::STATUS_PENDING_REVIEW
            : self::STATUS_PENDING;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function mp()
    {
        return $this->belongsTo(Mp::class, 'mp_id', 'mp_id');
    }
    public function facility(): BelongsTo
    {
        // Adjust 'facility_id' if your foreign key column on constituency_requests uses a different name
        return $this->belongsTo(ConstituencyFacility::class, 'facility_id', 'facility_id');
    }
    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }
}