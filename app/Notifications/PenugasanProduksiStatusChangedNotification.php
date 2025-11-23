<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\PenugasanProduksi;

class PenugasanProduksiStatusChangedNotification extends Notification
{
    use Queueable;

    protected $penugasan;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(PenugasanProduksi $penugasan, $oldStatus, $newStatus)
    {
        $this->penugasan = $penugasan;
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
            'title' => 'Status Produksi Berubah',
            'message' => 'Status penugasan produksi #' . $this->penugasan->penugasan_id . ' berubah dari ' . $this->oldStatus . ' menjadi ' . $this->newStatus . '.',
            'action_url' => route('penugasan-produksi.edit', $this->penugasan->penugasan_id),
            'type' => 'info',
            'penugasan_id' => $this->penugasan->penugasan_id,
        ];
    }
}
