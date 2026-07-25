<?php

namespace App\Notifications;

use App\Models\MaintenanceOrder;
use Illuminate\Notifications\Notification;

class MaintenanceOrderCreated extends Notification
{
    /**
     * The maintenance order instance.
     */
    protected MaintenanceOrder $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(MaintenanceOrder $order)
    {
        $this->order = $order;
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
            'title' => 'Nova Ordem de Manutenção',
            'message' => "Uma nova ordem de manutenção foi aberta para o equipamento {$this->order->equipment->name} (tipo: {$this->order->type->label()}, prioridade: {$this->order->priority->label()})",
            'type' => 'maintenance',
            'maintenance_order_id' => $this->order->id,
            'equipment_id' => $this->order->equipment_id,
            'priority' => $this->order->priority->value,
            'link' => '/maintenance/' . $this->order->id,
        ];
    }
}
