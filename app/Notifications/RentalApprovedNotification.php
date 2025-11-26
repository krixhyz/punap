<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalApprovedNotification extends Notification
{
    use Queueable;

    protected $rentalRequest;

    public function __construct(RentalRequest $rentalRequest)
    {
        $this->rentalRequest = $rentalRequest;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // send email + save to DB
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Rental Request Has Been Approved!')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Your rental request for "' . $this->rentalRequest->product->name . '" has been approved by the owner.')
            ->action('View Rental Details', route('rental.checkout', $this->rentalRequest->id))
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'rentalAccept',
            'rental_id' => $this->rentalRequest->id,
            'product_name' => $this->rentalRequest->product->name,
            'message' => 'Your rental request has been approved by the owner.',
        ];
    }
}
