<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pesanan;

class PesananStatusChangedNotification extends Notification
{
    use Queueable;

    protected $pesanan;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pesanan $pesanan, $oldStatus, $newStatus)
    {
        $this->pesanan = $pesanan;
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
            'title' => 'Status Pesanan Berubah',
            'message' => 'Status pesanan #' . $this->pesanan->pesanan_id . ' berubah dari ' . $this->oldStatus . ' menjadi ' . $this->newStatus . '.',
            'action_url' => route('pesanan.edit', $this->pesanan->pesanan_id),
            'type' => 'info',
            'pesanan_id' => $this->pesanan->pesanan_id,
        ];
    }
}
