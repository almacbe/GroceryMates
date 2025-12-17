<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MergeChecklistReplaced implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, array{id: string, text: string, checked: bool}>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $originId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('merge-checklist')];
    }

    public function broadcastAs(): string
    {
        return 'merge.checklist.replaced';
    }

    public function broadcastWith(): array
    {
        return [
            'items' => $this->items,
            'originId' => $this->originId,
        ];
    }
}

