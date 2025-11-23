<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pembelian;

class PurchaseOrderCreatedNotification extends Notification
{
    use Queueable;

    protected $pembelian;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pembelian $pembelian)
    {
        $this->pembelian = $pembelian;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Purchase Order Baru',
            'message' => 'PO #' . $this->pembelian->pembelian_id . ' telah dibuat untuk Pemasok ' . ($this->pembelian->pemasok->nama_pemasok ?? 'Unknown') . '.',
            'action_url' => route('pembelian.show', $this->pembelian->pembelian_id),
            'type' => 'success',
            'pembelian_id' => $this->pembelian->pembelian_id,
        ];
    }
}
