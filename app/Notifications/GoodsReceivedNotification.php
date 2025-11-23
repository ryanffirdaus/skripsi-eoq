<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\PenerimaanBahanBaku;

class GoodsReceivedNotification extends Notification
{
    use Queueable;

    protected $penerimaan;

    /**
     * Create a new notification instance.
     */
    public function __construct(PenerimaanBahanBaku $penerimaan)
    {
        $this->penerimaan = $penerimaan;
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
            'title' => 'Bahan Baku Diterima',
            'message' => 'Penerimaan #' . $this->penerimaan->penerimaan_id . ' dari PO #' . $this->penerimaan->pembelian_id . ' telah selesai.',
            'action_url' => route('penerimaan-bahan-baku.edit', $this->penerimaan->penerimaan_id),
            'type' => 'success',
            'penerimaan_id' => $this->penerimaan->penerimaan_id,
        ];
    }
}
