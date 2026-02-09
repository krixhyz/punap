<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalRequest;

class RentalRequestNotification extends Notification
{
    use Queueable;

    public $rentalRequest;

    public function __construct(RentalRequest $rentalRequest)
{
    $this->rentalRequest = $rentalRequest;
}

    public function via($notifiable)
    {
        return ['database']; // Store in database
    }

    public function toDatabase($notifiable)
{
    return [
        'type' => 'rental',
        'rental_request_id' => $this->rentalRequest->id,
        'product_id' => $this->rentalRequest->product_id,
        'renter_id' => $this->rentalRequest->renter_id,
        'message' => "You have a new rental request."
    ];

}
}
