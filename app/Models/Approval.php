<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property int $level
 * @property int $approver_id
 * @property DocumentStatus $status
 * @property ?string $note
 * @property ?\Illuminate\Support\Carbon $approved_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property-read Model $approvable
 */
class Approval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'level',
        'approver_id',
        'status',
        'note',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
