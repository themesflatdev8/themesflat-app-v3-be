<?php


namespace App\Services\Shopify;


use GuzzleHttp\Client;

abstract class BaseShopifyService
{

    protected $shopifyDomain;
    protected $accessToken;
    protected $sentry;


    /**
     * @param string $shopifyDomain
     * @param string $accessToken
     */
    public function setShopifyHeader(string $shopifyDomain, string $accessToken)
    {
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
    }

    /**
     * @param string $url
     * @param array $data
     * @param string $responseType
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function get(string $url, array $data = [], $responseType = 'content')
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'GET',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
                'query' => $data,
            ]
        );

        if ($responseType == 'content') {
            return json_decode($response->getBody()->getContents());
        } else {
            return $response;
        }
    }


    /**
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function post(string $url, array $data = [])
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'POST',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
                'body' => json_encode($data),
            ]
        );

        return json_decode($response->getBody()->getContents());
    }


    /**
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function put(string $url, array $data = [])
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'PUT',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
                'body' => json_encode($data),
            ]
        );

        return json_decode($response->getBody()->getContents());
    }


    /**
     * @param string $url
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function drop(string $url)
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'DELETE',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents());
    }
}
