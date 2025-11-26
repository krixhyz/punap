<?php

namespace App\Events;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TestBroadcast implements ShouldBroadcastNow
{
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.1'); // replace with your test user ID
    }

    public function broadcastWith()
    {
        return ['message' => 'Reverb is working!'];
    }
}
