<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pembelian;

class PembelianStatusChangedNotification extends Notification
{
    use Queueable;

    protected $pembelian;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pembelian $pembelian, $oldStatus, $newStatus)
    {
        $this->pembelian = $pembelian;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
            'title' => 'Status PO Berubah',
            'message' => 'Status Purchase Order #' . $this->pembelian->pembelian_id . ' berubah dari ' . $this->oldStatus . ' menjadi ' . $this->newStatus . '.',
            'action_url' => route('pembelian.edit', $this->pembelian->pembelian_id),
            'type' => 'info',
            'pembelian_id' => $this->pembelian->pembelian_id,
        ];
    }
}
