<?php

namespace Modules\Auth\Lib;

use Ramsey\Uuid\Uuid;
use Shopify\Auth\OAuth;
use Shopify\Auth\Session;
use Shopify\Clients\Http;
use Shopify\Context;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\InvalidArgumentException;
use Shopify\Exception\SessionStorageException;
use Shopify\Utils;

class TAuth extends OAuth
{
    public function __construct()
    {
        $host = str_replace('https://', '', config('app.url', 'not_defined'));
        $host = str_replace('http://', '', $host);

        Context::initialize(
            config('fa_common.shopify_api_key', 'not_defined'),
            config('fa_common.shopify_api_secret', 'not_defined'),
            implode(',', config('fa_shopify.scopes', 'not_defined')),
            $host,
            new DbSessionStorage(),
            // the following four params are needed in order to set userAgentPrefix
            '2022-04',                  // apiVersion
            true,                       // isEmbeddedApp, default = true
            false,                      // isPrivateApp, default = false
            null,                       // privateAppStorefrontAccessToken, default = null
            'PHP app template/unknown'   // userAgentPrefix
        );
    }

    /**
     * Initializes a session and cookie for the OAuth process, and returns the authorization url
     *
     * @param string        $shop              A Shopify domain name or hostname
     * @param string        $redirectPath      Redirect path for callback
     * @param bool          $isOnline          Whether or not the session is online
     * @param null|callable $setCookieFunction An optional override for setting cookie in response
     *
     * @return string The URL for OAuth redirection
     * @throws \Shopify\Exception\CookieSetException
     * @throws \Shopify\Exception\PrivateAppException
     * @throws \Shopify\Exception\SessionStorageException
     * @throws \Shopify\Exception\UninitializedContextException
     */
    public static function begin(
        string $shop,
        string $redirectPath,
        bool $isOnline,
        ?callable $setCookieFunction = null
    ): string {
        Context::throwIfUninitialized();
        Context::throwIfPrivateApp("OAuth is not allowed for private apps");

        $sanitizedShop = Utils::sanitizeShopDomain($shop);

        if (!isset($sanitizedShop)) {
            throw new InvalidArgumentException("Invalid shop domain: $shop");
        }

        $redirectPath = trim(strtolower($redirectPath));
        $redirectPath = ($redirectPath[0] == '/') ? $redirectPath : '/' . $redirectPath;
        $mySessionId = self::getOfflineSessionId($sanitizedShop);
        $session = new Session($mySessionId, $sanitizedShop, false, Uuid::uuid4()->toString());
        $grantOptions = '';
        $sessionStored = Context::$SESSION_STORAGE->storeSession($session);

        if (!$sessionStored) {
            throw new SessionStorageException(
                'OAuth Session could not be saved. Please check your session storage functionality.'
            );
        }

        $query = [
            'client_id' => Context::$API_KEY,
            'scope' => Context::$SCOPES->toString(),
            'redirect_uri' => 'https://' . Context::$HOST_NAME . $redirectPath,
            'state' => $session->getState(),
            'grant_options[]' => $grantOptions,
        ];

        return "https://{$sanitizedShop}/admin/oauth/authorize?" . http_build_query($query);
        // $domainName = str_replace(".myshopify.com","",$sanitizedShop);
        // return "https://admin.shopify.com/store/".$domainName."/oauth/authorize?" . http_build_query($query);
    }

    public function getToken($code, $shop)
    {
        $post = [
            'client_id' => Context::$API_KEY,
            'client_secret' => Context::$API_SECRET_KEY,
            'code' => $code,
        ];

        $client = new Http($shop);
        $response = self::requestAccessToken($client, $post);
        if ($response->getStatusCode() !== 200) {
            throw new HttpRequestException("Failed to get access token: {$response->getDecodedBody()}");
        }

        return $response->getDecodedBody()['access_token'];
    }

    public function getExchangeToken($idToken, $shop)
    {
        $post = [
            'client_id' => Context::$API_KEY,
            'client_secret' => Context::$API_SECRET_KEY,
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token' => $idToken,
            'subject_token_type' => 'urn:ietf:params:oauth:token-type:id_token',
            'requested_token_type' => 'urn:shopify:params:oauth:token-type:offline-access-token'
        ];

        $client = new Http($shop);
        $response = self::requestAccessToken($client, $post);
        if ($response->getStatusCode() !== 200) {
            throw new HttpRequestException("Failed to get access token: {$response->getDecodedBody()}");
        }

        return $response->getDecodedBody()['access_token'];
    }
}
