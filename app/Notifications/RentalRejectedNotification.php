<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalRejectedNotification extends Notification
{
    use Queueable;

    protected $rentalRequest;

    public function __construct(RentalRequest $rentalRequest)
{
    $this->rentalRequest = $rentalRequest;
}

    public function via($notifiable)
    {
        return ['mail', 'database']; // Send as email + save to DB
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Rental Request Has Been Rejected')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Unfortunately, your rental request for "' . $this->rentalRequest->product->name . '" has been declined by the owner.')
            ->line('The item is still available for other rentals or purchases.')
            ->action('Browse More Items', route('products.index'))
            ->line('Thank you for understanding.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'rentalReject',
            'rental_id' => $this->rentalRequest->id,
            'product_name' => $this->rentalRequest->product->name,
            'message' => 'Your rental request has been rejected by the owner.',
        ];
    }
}
