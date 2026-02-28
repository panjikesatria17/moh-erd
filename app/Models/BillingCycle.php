<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Invoice;
use App\Models\Sppg;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingCycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sppg_id',
        'week_start_date',
        'week_end_date',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date' => 'date',
            'status' => DocumentStatus::class,
        ];
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
