<?php

namespace App\Models;

use App\Models\Delivery;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectedItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_id',
        'purchase_order_item_id',
        'product_id',
        'reported_by',
        'quantity',
        'reason',
        'evidence_image_path',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'reported_at' => 'date',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
