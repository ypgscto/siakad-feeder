<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeederSyncLog extends Model
{
    protected $fillable = [
        'sync_type',
        'payload_summary',
        'feeder_error_code',
        'feeder_error_desc',
        'success',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'payload_summary' => 'array',
            'success' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
