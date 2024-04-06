<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $chatId;

    private string $messageId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $chatId, string $messageId)
    {
        $this->chatId = $chatId;
        $this->messageId = $messageId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat.'.$this->chatId),
        ];
    }

    public function broadcastWith(): array
    {
        return ['messageId' => $this->messageId];
    }

    public function broadcastAs(): string
    {
        return 'message_deleted';
    }
}
