<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pesanan;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected $pesanan;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pesanan $pesanan)
    {
        $this->pesanan = $pesanan;
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
            'title' => 'Pesanan Baru Masuk',
            'message' => 'Pesanan baru #' . $this->pesanan->pesanan_id . ' dari ' . ($this->pesanan->pelanggan->nama_pelanggan ?? 'Pelanggan') . ' perlu diproses.',
            'action_url' => route('pesanan.show', $this->pesanan->pesanan_id),
            'type' => 'info',
            'pesanan_id' => $this->pesanan->pesanan_id,
        ];
    }
}
