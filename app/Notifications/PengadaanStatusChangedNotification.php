<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pengadaan;

class PengadaanStatusChangedNotification extends Notification
{
    use Queueable;

    protected $pengadaan;
    protected $statusLabel;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pengadaan $pengadaan, $statusLabel)
    {
        $this->pengadaan = $pengadaan;
        $this->statusLabel = $statusLabel;
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
            'title' => 'Status Pengadaan Berubah',
            'message' => 'Pengadaan #' . $this->pengadaan->pengadaan_id . ' telah berubah status menjadi: ' . $this->statusLabel,
            'action_url' => route('pengadaan.show', $this->pengadaan->pengadaan_id),
            'type' => 'info',
            'pengadaan_id' => $this->pengadaan->pengadaan_id,
            'status' => $this->pengadaan->status,
        ];
    }
}
