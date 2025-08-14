<?php

namespace Modules\Auth\Events;

use Illuminate\Queue\SerializesModels;

class InstallCompleteEvent
{
    use SerializesModels;
    public $store;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($store)
    {
        $this->store = $store;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
