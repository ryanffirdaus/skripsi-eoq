<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    protected $item;
    protected $type; // 'bahan_baku' or 'produk'

    /**
     * Create a new notification instance.
     */
    public function __construct($item, $type)
    {
        $this->item = $item;
        $this->type = $type;
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
        $name = $this->type === 'bahan_baku' ? $this->item->nama_bahan : $this->item->nama_produk;
        $id = $this->type === 'bahan_baku' ? $this->item->bahan_baku_id : $this->item->produk_id;
        $stock = $this->type === 'bahan_baku' ? $this->item->stok_bahan : $this->item->stok_produk;
        $rop = $this->type === 'bahan_baku' ? $this->item->rop_bahan : $this->item->rop_produk;

        return [
            'title' => 'Stok Menipis (Di Bawah ROP)',
            'message' => "Stok {$name} ({$stock}) telah mencapai titik pemesanan ulang ({$rop}). Segera lakukan pengadaan.",
            'action_url' => $this->type === 'bahan_baku' ? route('bahan-baku.show', $id) : route('produk.show', $id),
            'type' => 'warning',
            'item_id' => $id,
            'item_type' => $this->type,
        ];
    }
}
