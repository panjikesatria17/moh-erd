<?php

namespace App\Notifications;

use App\Models\PurchaseFundingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PurchaseFundingNeedsOwnerApproval extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PurchaseFundingRequest $purchaseFundingRequest,
        private readonly ?User $reviewedBy = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengajuan dana menunggu approval owner',
            'message' => sprintf(
                '%s (%s) menunggu approval owner. Nominal review: Rp %s.',
                $this->purchaseFundingRequest->number,
                $this->purchaseFundingRequest->purchaseOrder?->number ?? 'Tanpa PO',
                number_format((float) ($this->purchaseFundingRequest->reviewed_amount ?? 0), 0, ',', '.')
            ),
            'funding_request_id' => $this->purchaseFundingRequest->id,
            'funding_request_number' => $this->purchaseFundingRequest->number,
            'purchase_order_number' => $this->purchaseFundingRequest->purchaseOrder?->number,
            'reviewed_amount' => (float) ($this->purchaseFundingRequest->reviewed_amount ?? 0),
            'reviewed_by_name' => $this->reviewedBy?->name,
            'url' => route('ui.purchase-funding-requests.index', ['status' => 'reviewed']),
        ];
    }
}
