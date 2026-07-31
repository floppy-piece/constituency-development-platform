<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueCluster extends Model
{
    use HasFactory;

    protected $table = 'issue_clusters';
    protected $primaryKey = 'cluster_id';

    public const TREND_RISING = 'rising';
    public const TREND_STABLE = 'stable';
    public const TREND_FALLING = 'falling';

    protected $fillable = [
        'mp_id',
        'ward_id',
        'category',
        'theme_label',
        'report_count',
        'first_seen_at',
        'last_seen_at',
        'trend',
        'centroid_lat',
        'centroid_lng',
        'severity_score',
        'ward_ids',
    ];

    protected $casts = [
        'report_count' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'centroid_lat' => 'float',
        'centroid_lng' => 'float',
        'severity_score' => 'integer',
        'ward_ids' => 'array',
    ];

    public function mp(): BelongsTo
    {
        return $this->belongsTo(Mp::class, 'mp_id', 'mp_id');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ConstituencyRequest::class, 'cluster_id', 'cluster_id');
    }

    public function summaryLine(?string $wardName = null): string
    {
        $area = $wardName ?: 'multiple wards';
        $trendText = match ($this->trend) {
            self::TREND_RISING => 'trending up',
            self::TREND_FALLING => 'trending down',
            default => 'stable',
        };

        return sprintf(
            'Recurring theme: %s — %d reports, %s, %s',
            $this->theme_label,
            $this->report_count,
            $area,
            $trendText
        );
    }
}
