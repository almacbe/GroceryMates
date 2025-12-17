<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MergeStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array{listA: string, listB: string}  $state
     */
    public function __construct(
        public readonly array $state,
        public readonly ?string $originId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('merge-state')];
    }

    public function broadcastAs(): string
    {
        return 'merge.state.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'state' => $this->state,
            'originId' => $this->originId,
        ];
    }
}

