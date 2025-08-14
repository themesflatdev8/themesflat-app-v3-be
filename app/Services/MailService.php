<?php

namespace App\Services;

use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;
use Google_Client;
use League\OAuth2\Client\Provider\Google;
use Illuminate\Support\Facades\Cache;
use App\Mail\Loyalty;

class MailService
{
    protected $provider;
    protected $client_id;
    protected $client_secret;
    protected $refresh_token;
    protected $email;
    protected $name;

    public function __construct()
    {
        $this->client_id = env('GMAIL_API_CLIENT_ID');
        $this->client_secret = env('GMAIL_API_CLIENT_SECRET');
        $this->refresh_token = $this->getRefreshToken();
        $this->email = env('MAIL_FROM_ADDRESS');
        $this->name = 'Fether App';

        $this->provider = new Google([
            'clientId' => $this->client_id,
            'clientSecret' => $this->client_secret,
        ]);
    }

    public function sendLoyaltyEmail($recipientEmail, Loyalty $loyalty)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setOAuth(new OAuth([
                'provider' => $this->provider,
                'clientId' => $this->client_id,
                'clientSecret' => $this->client_secret,
                'refreshToken' => $this->refresh_token,
                'userName' => $this->email,
            ]));

            $mail->setFrom($this->email, $this->name);
            $mail->addAddress($recipientEmail);

            $mail->isHTML(true);
            $mail->Subject = "Congratulations! You're now an official Fether's Loyalty member";
            $mail->Body = $loyalty->render();

            $mail->send();
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    private function getRefreshToken()
    {
        $refresh_token = Cache::get('google_refresh_token');
        
        if (!$refresh_token) {
            $client = new Google_Client();
            $client->setClientId($this->client_id);
            $client->setClientSecret($this->client_secret);
            $client->refreshToken(env('GMAIL_API_REFRESH_TOKEN'));

            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $refresh_token = $client->getRefreshToken();

            Cache::put('google_refresh_token', $refresh_token, 3600);
        }

        return $refresh_token;
    }
}
