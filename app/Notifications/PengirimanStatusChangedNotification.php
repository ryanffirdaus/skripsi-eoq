<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pengiriman;

class PengirimanStatusChangedNotification extends Notification
{
    use Queueable;

    protected $pengiriman;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pengiriman $pengiriman, $oldStatus, $newStatus)
    {
        $this->pengiriman = $pengiriman;
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
            'title' => 'Status Pengiriman Berubah',
            'message' => 'Status pengiriman #' . $this->pengiriman->pengiriman_id . ' berubah dari ' . $this->oldStatus . ' menjadi ' . $this->newStatus . '.',
            'action_url' => route('pengiriman.edit', $this->pengiriman->pengiriman_id),
            'type' => 'info',
            'pengiriman_id' => $this->pengiriman->pengiriman_id,
        ];
    }
}
