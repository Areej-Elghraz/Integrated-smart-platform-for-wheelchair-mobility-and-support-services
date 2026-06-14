<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'wheelchair_id',
        'trip_id',
        'type',
        'severity',
        'message',
        'data',
        'event_source',
        'read_at',
        'resolved_at',
    ];

    protected $casts = [
        'data'        => 'array',
        'read_at'     => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Find an existing unresolved event with same (type, severity, event_source) for this wheelchair.
     * Used for deduplication: if found, update updated_at; otherwise create new.
     */
    public static function findDuplicate(int $wheelchairId, string $type, string $severity, string $eventSource): ?self
    {
        return self::where('wheelchair_id', $wheelchairId)
            ->where('type', $type)
            ->where('severity', $severity)
            ->where('event_source', $eventSource)
            ->whereNull('resolved_at')
            ->latest()
            ->first();
    }

    public function wheelchair(): BelongsTo
    {
        return $this->belongsTo(Wheelchair::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
