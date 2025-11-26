<?php

namespace App\Notifications;

use App\Models\SwapRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class SwapRequested extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $swapRequest;

    public function __construct(SwapRequest $swapRequest)
    {
        $this->swapRequest = $swapRequest;
    }

    // Channels: database + broadcast
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'swap',
            'swap_request_id' => $this->swapRequest->id,
            'requester_id' => $this->swapRequest->requester_id,
            'product_id' => $this->swapRequest->product_id,
            'offered_product_id' => $this->swapRequest->offered_product_id,
            'offered_amount' => $this->swapRequest->offered_amount,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'swap_request_id' => $this->swapRequest->id,
            'requester_name' => $this->swapRequest->requester->name,
            'product_title' => $this->swapRequest->product->title,
            'offered_product_title' => $this->swapRequest->offeredProduct?->title,
            'offered_amount' => $this->swapRequest->offered_amount,
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->swapRequest->owner_id);
    }

    public function broadcastAs()
    {
        return 'swap.requested';
    }
}
