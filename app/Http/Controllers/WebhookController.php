<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\CollectionWebhookJob;
use App\Jobs\DeleteProductsJob;
use App\Jobs\ProductWebhookJob;
use App\Jobs\ShopUpdateWebhookJob;
use App\Models\ProductModel;
use App\Models\ShopModel;
use App\Services\App\OrderService;
use Exception;
use Google\Service\AndroidPublisher\Order;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public $shopModel;
    public $productModel;
    public $sentry;

    public function __construct(ShopModel $shopModel, ProductModel $productModel)
    {
        $this->middleware('webhook.verify_header');
        $this->shopModel = $shopModel;
        $this->productModel = $productModel;
        $this->sentry = app('sentry');
    }

    public function uninstall(Request $request)
    {
        try {
            $domain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $shop = $this->shopModel->where('shop', $domain)->first();
            if (!empty($shop)) {
                $shop->update([
                    'is_active' => 0,
                    'access_token' => null,
                    // 'trial_days' => getTrialDays($shop),
                    // 'trial_on' => null,
                    'cancelled_on' => date('Y-m-d H:i:s', time())
                ]);
            }

            SystemCache::remove('getBundleStorefront_' . $shop->shop);

            // dispatch(new DeleteProductsJob($shop->shop_id));
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function shopUpdate(Request $request)
    {
        try {
            $domain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $res = $request->all();

            dispatch(new ShopUpdateWebhookJob(
                $domain,
                $res
            ));
        } catch (Exception $exception) {
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function themPublish(Request $request)
    {
        return response()->json(['status' => 'success'], 200);
    }

    public function shopifyRequest(Request $request)
    {
        return response()->json(['status' => 'success'], 200);
    }

    public function orderCreate(Request $request)
    {
        try {
            $domain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $order = $request->all();
            /** @var \App\Services\App\OrderService $orderService */
            $orderService = app(OrderService::class);

            $orderService->createOrder($domain, $order);
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return response()->json(['status' => 'success'], 200);
    }

    public function localeUpdate(Request $request)
    {
        return response()->json(['status' => 'success'], 200);
    }



    public function productCreate(Request $request)
    {
        // return response()->json(['status' => 'success'], 200);
        try {
            $shopDomain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $product = $request->all();

            dispatch(new ProductWebhookJob(
                $shopDomain,
                "create",
                $product
            ));
        } catch (Exception $exception) {
            // $this->sentry->captureException($exception);
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function productUpdate(Request $request)
    {
        // return response()->json(['status' => 'success'], 200);
        try {
            $shopDomain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $product = $request->all();
            dispatch(new ProductWebhookJob(
                $shopDomain,
                "update",
                $product
            ));
        } catch (Exception $exception) {
            // Log::info(print_r($exception->getMessage(), true));
            // $this->sentry->captureException($exception);
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function productDelete(Request $request)
    {
        $shopDomain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
        $productId = $request->get('id');
        $product = [
            'id' => $productId
        ];

        dispatch(new ProductWebhookJob(
            $shopDomain,
            "delete",
            $product
        ));

        return response()->json(['status' => 'success'], 200);
    }



    public function collectionCreate(Request $request)
    {
        try {
            $shopDomain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $product = $request->all();

            dispatch(new CollectionWebhookJob(
                $shopDomain,
                "create",
                $product
            ));
        } catch (Exception $exception) {
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function collectionUpdate(Request $request)
    {
        try {
            $shopDomain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
            $product = $request->all();
            dispatch(new CollectionWebhookJob(
                $shopDomain,
                "update",
                $product
            ));
        } catch (Exception $exception) {
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function collectionDelete(Request $request)
    {
        $shopDomain = $request->server('HTTP_X_SHOPIFY_SHOP_DOMAIN');
        $productId = $request->get('id');
        $product = [
            'id' => $productId
        ];

        dispatch(new CollectionWebhookJob(
            $shopDomain,
            "delete",
            $product
        ));

        return response()->json(['status' => 'success'], 200);
    }
}
