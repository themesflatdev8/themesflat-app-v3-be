<?php

namespace Modules\Auth\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class SyncSuccessEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    protected $shopId;
    protected $resource;
    protected $status;

    public function __construct($shopId, $resource, $status)
    {
        $this->shopId = $shopId;
        $this->resource = $resource;
        $this->status = $status;
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
    /**
     * Payload trả về cho client
     */
    public function broadcastWith(): array
    {
        return [
            'resource' => $this->resource,
            'status'   => $this->status,
        ];
    }
}
