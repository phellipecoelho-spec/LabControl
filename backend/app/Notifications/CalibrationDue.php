<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CalibrationDue extends Notification
{
    // Marker class — data is stored directly in JSON via the CheckCalibrationDue command
    // This class is required for polymorphic type resolution in the notifications table
}
