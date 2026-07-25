<?php

namespace App\Notifications;

use App\Models\Verification;
use Illuminate\Notifications\Notification;

class ToleranceExceeded extends Notification
{
    /**
     * The verification instance.
     */
    protected Verification $verification;

    /**
     * Create a new notification instance.
     */
    public function __construct(Verification $verification)
    {
        $this->verification = $verification;
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
            'message' => "Tolerância excedida na aferição do equipamento {$this->verification->equipment->name}.",
            'equipment_id' => $this->verification->equipment_id,
            'equipment_name' => $this->verification->equipment->name,
            'verification_id' => $this->verification->id,
            'verified_at' => $this->verification->verified_at?->toISOString(),
            'type' => 'tolerance_exceeded',
            'severity' => 'warning',
        ];
    }
}
