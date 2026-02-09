<?php

namespace App\Notifications;

use App\Models\SwapRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class SwapAccepted extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $swapRequest;

    public function __construct(SwapRequest $swapRequest)
    {
        $this->swapRequest = $swapRequest;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'swapAccept',
            'swap_request_id' => $this->swapRequest->id,
            'product_id' => $this->swapRequest->product_id,
            'offered_product_id' => $this->swapRequest->offered_product_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'swap_request_id' => $this->swapRequest->id,
            'product_title' => $this->swapRequest->product->title,
            'offered_product_title' => $this->swapRequest->offeredProduct?->title,
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->swapRequest->requester_id);
    }

    public function broadcastAs()
    {
        return 'swap.accepted';
    }
}
