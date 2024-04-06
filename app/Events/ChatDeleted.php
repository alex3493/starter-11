<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int|string $chat_id;

    /**
     * Create a new event instance.
     */
    public function __construct(int|string $chat_id)
    {
        $this->chat_id = $chat_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.updates'),
            new PrivateChannel('chat.updates.'.$this->chat_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'chatId' => $this->chat_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat_deleted';
    }
}
