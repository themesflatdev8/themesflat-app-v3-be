<?php

namespace Modules\Auth\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;

class SyncSuccessEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    protected $shopId;
    protected $resource;

    public function __construct($shopId, $resource, $status)
    {
        $this->shopId = $shopId;
        $this->resource = $resource;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [
            new Channel('list-syncing-' . $this->shopId),
        ];
    }

    /**
     * Tên sự kiện (event name).
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        // Tên sự kiện phải khớp với tên bạn lắng nghe ở client (ví dụ: `sync-completed`)
        return 'sync-completed';
    }
}
