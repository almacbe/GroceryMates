<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MergeChecklistItemUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $id,
        public readonly bool $checked,
        public readonly ?string $originId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('merge-checklist')];
    }

    public function broadcastAs(): string
    {
        return 'merge.checklist.item.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'checked' => $this->checked,
            'originId' => $this->originId,
        ];
    }
}

