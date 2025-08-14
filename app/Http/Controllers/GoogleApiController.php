<?php

namespace App\Http\Controllers;

use App\Mail\Loyalty;
use App\Models\GGTokenModel;
use App\Models\LoyaltyModel;
use App\Models\StoreModel;
use App\Services\GoogleApiService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GoogleApiController extends Controller
{
    protected $clientSecretFile;
    protected $googleApiService;
    public $sentry;

    public function __construct(GoogleApiService $googleApiService)
    {
        $this->clientSecretFile = base_path() . "/config/google_client.json";
        $this->googleApiService = $googleApiService;
        $this->sentry = app('sentry');
    }

    public function analyticsOauth2(Request $request)
    {
        $auth_url = $this->googleApiService->getAnalyticsAuthUrl();

        return redirect($auth_url);
    }

    public function analyticsOauth2Callback()
    {
        $client = $this->googleApiService->buildClient();
        $state = json_decode(base64_decode($_GET["state"]), true);

        if (!isset($_GET['code'])) {
            $auth_url = $this->googleApiService->getAnalyticsAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        } else {
            $client->authenticate($_GET['code']);
            $token = $client->getAccessToken();

            if (!empty($token)) {
                GGTokenModel::updateOrCreate(
                    ['key' => 'token'],
                    ['value' => $token]
                );
                dd($token);
            }
        }
    }

    public function hook(Request $request)
    {
        try {
            $data = $request->all();
            if (!empty($data['message'])) {
                $payload = $data['message']['data'];
                // $dataMes = json_decode(base64_decode($payload));

                $token = $this->googleApiService->getToken();
                $client = new Client();
                // tìm theo email và subject
                $url = "https://gmail.googleapis.com/gmail/v1/users/vosydao88@gmail.com/messages?maxResults=2&labelIds[]=IINBOX&q=from:fether-team@googlegroups.com subject:star review for FT: Frequently Bought Together";
                $res = $client->get(
                    $url,
                    [
                        'headers' => [
                            "Authorization" => "Bearer " . $token['access_token']
                        ]
                    ]
                );

                if ($res->getStatusCode() == 200) {
                    $dataHistory = json_decode($res->getBody()->getContents());
                    if (!empty($dataHistory)) {
                        $messages = $dataHistory->messages;
                        foreach ($messages  as $message) {
                            $id = $message->id;
                            $url = "https://gmail.googleapis.com/gmail/v1/users/vosydao88@gmail.com/messages/$id";
                            $res = $client->get(
                                $url,
                                [
                                    'headers' => [
                                        "Authorization" => "Bearer " . $token['access_token']
                                    ]
                                ]
                            );

                            if ($res->getStatusCode() == 200) {
                                $response = json_decode($res->getBody()->getContents());
                                if ($response) {
                                    $payload = $response->payload;
                                    // dump($payload);
                                    // $body = $payload->body;
                                    $header = $payload->headers;
                                    foreach ($header as $head) {
                                        if ($head->name == "Subject") {
                                            $subject = $head->value;

                                            $array = explode('-star review for FT: Frequently Bought Together by ', $subject);
                                            $star = str_replace('New ', "", $array[0]);
                                            $stores = StoreModel::where('name', 'LIKE', "%" . $array[1] . "%")->get();
                                            $countStore = $stores->count();
                                            $store = $stores->first();
                                            if (
                                                $countStore < 2 &&
                                                !empty($store) && !str_contains($store->owner, 'My Store') && !str_contains($store->name, 'My Store')
                                                && !str_contains($store->owner, 'Ma boutique') && !str_contains($store->name, 'Ma boutique')
                                            ) {
                                                $storeId = $store->store_id;

                                                LoyaltyModel::updateOrCreate(
                                                    ['store_id' => $storeId],
                                                    ['quest_review' => $star]
                                                );


                                                checkLoyalty($store);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }

        return true;
    }
}
