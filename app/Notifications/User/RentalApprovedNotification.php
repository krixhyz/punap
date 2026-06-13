<?php

namespace App\Notifications\User;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class RentalApprovedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    protected $rentalRequest;

    public function __construct(RentalRequest $rentalRequest)
    {
        $this->rentalRequest = $rentalRequest;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Rental Request Has Been Approved!')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Your rental request for "' . $this->rentalRequest->product->title . '" has been approved by the owner.')
            ->line('Log in to Punap to proceed with payment.')
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'type'              => 'rentalAccept',
            'rental_request_id' => $this->rentalRequest->id,
            'product_title'     => $this->rentalRequest->product->title,
            'message'           => 'Your rental request has been approved by the owner.',
            'redirect_url'      => route('rental.payment', $this->rentalRequest->id),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type'              => 'rentalAccept',
            'rental_request_id' => $this->rentalRequest->id,
            'product_title'     => optional($this->rentalRequest->product)->title,
            'message'           => 'Your rental request has been approved. Proceed to payment.',
            'redirect_url'      => route('rental.payment', $this->rentalRequest->id),
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->rentalRequest->renter_id);
    }

    public function broadcastAs()
    {
        return 'rental.approved';
    }
}
