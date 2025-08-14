<?php

namespace App\Services;

use App\Models\GGTokenModel;
use Google_Client;

class GoogleApiService extends AbstractService
{
    protected $clientSecretFile;
    protected $sentry;

    public function __construct()
    {
        $this->clientSecretFile = base_path() . "/config/google_client.json";
        $this->sentry = app('sentry');
    }

    public function buildClient()
    {
        try {
            $client = new Google_Client();
            $client->setAuthConfigFile($this->clientSecretFile);
            $client->setRedirectUri(route("google.auth.callback"));
            $client->addScope(["https://mail.google.com/", "https://www.googleapis.com/auth/cloud-platform", "https://www.googleapis.com/auth/pubsub"]);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            $client->setIncludeGrantedScopes(true);

            return $client;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }
    }

    public function getToken()
    {
        try {
            $data = GGTokenModel::where('key', 'token')->first();
            $token = $data->value;
            $client = $this->buildClient();

            $client->setAccessToken($token);
            // Refresh the token if it's expired.
            if ($client->isAccessTokenExpired()) {
                $client->refreshToken($token["refresh_token"]);
                $newToken = $client->getAccessToken();

                GGTokenModel::updateOrCreate(
                    ['key' => 'token'],
                    ['value' => $newToken]
                );

                return (array) $newToken;
            }

            return $token;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }
    }

    public function getAnalyticsAuthUrl()
    {
        // dd(route("google.auth.callback"));

        $client = $this->buildClient();
        $paramPassCallBack = [
            // "store_id" => $storeId,
            // "page_path" => $pagePath
        ];
        $paramPassCallBack = base64_encode(json_encode($paramPassCallBack));
        $client->setState($paramPassCallBack);

        $auth_url = $client->createAuthUrl();

        return $auth_url;
    }
}
