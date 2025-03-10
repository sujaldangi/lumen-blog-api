<?php
namespace App\Events;

// use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
        \Log::info('MessageSent event triggered with message: ' . $this->message);
    }

    public function broadcastOn()
    {
        return new Channel('chat-channel');
    }
    public function broadcastAs()
    {
        return 'MessageSent';
    }
    public function shouldBroadcastNow()
    {
        return true;
    }
}

