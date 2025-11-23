<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Pengadaan;

class NewPengadaanNotification extends Notification
{
    use Queueable;

    protected $pengadaan;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pengadaan $pengadaan)
    {
        $this->pengadaan = $pengadaan;
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
            'title' => 'Pengajuan Pengadaan Baru',
            'message' => 'Pengadaan baru #' . $this->pengadaan->pengadaan_id . ' telah dibuat untuk Pesanan #' . $this->pengadaan->pesanan_id . '.',
            'action_url' => route('pengadaan.edit', $this->pengadaan->pengadaan_id),
            'type' => 'info',
            'pengadaan_id' => $this->pengadaan->pengadaan_id,
        ];
    }
}
