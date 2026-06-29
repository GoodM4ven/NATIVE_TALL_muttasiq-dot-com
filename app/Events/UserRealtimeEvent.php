<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRealtimeEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $telegramId,
        public string $type,
        public ?int $targetTokenId = null,
        ?string $socketId = null,
        public ?string $targetSessionId = null,
    ) {
        $normalizedSocketId = trim((string) $socketId);

        $this->socket = in_array($normalizedSocketId, ['', 'undefined', 'null'], true)
            ? null
            : $normalizedSocketId;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->telegramId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user-realtime';
    }

    /**
     * @return array{type: string, target_token_id: int|null, target_session_id: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'target_token_id' => $this->targetTokenId,
            'target_session_id' => $this->targetSessionId,
        ];
    }
}
