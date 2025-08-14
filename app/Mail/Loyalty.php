<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Loyalty extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $store;

    public function __construct($store)
    {
        $this->store = $store;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $store = $this->store;

        return $this
            ->subject("Congratulations! You're now an official Fether's Loyalty member")
            ->view('mail.loyalty', compact('store'));
    }
}
