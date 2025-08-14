<?php

namespace App\Jobs;

use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class SendLoyaltyEmailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $recipientEmail;
    protected $emailContent;

    public function __construct($recipientEmail, $emailContent)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->recipientEmail = $recipientEmail;
        $this->emailContent = $emailContent;
    }

    public function handle(MailService $mailService)
    {
        try {
            $mailService->sendLoyaltyEmail($this->recipientEmail, $this->emailContent);
        } catch (Exception $e) {
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }
}
