<?php

namespace App\Http\Controllers;

use App\Models\LanguageModel;
use App\Models\StoreModel;
use Exception;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public $languageModel;
    public $storeModel;
    public $sentry;

    public function __construct(StoreModel $storeModel, LanguageModel $languageModel)
    {
        $this->storeModel = $storeModel;
        $this->languageModel = $languageModel;
        $this->sentry = app('sentry');
    }

    public function getLanguageDefault(Request $request)
    {
        $store = $request->storeInfo;
        $laguage = LanguageModel::where('store_id', $store->store_id)->where('is_primary', 1)->first();

        return response([
            'data' => $laguage
        ]);
    }

    public function getLanguages(Request $request)
    {
        $store = $request->storeInfo;
        $filters = !empty($request->filters) ? (array) json_decode($request->filters) : [];
        $query = LanguageModel::where('store_id', $store->store_id);
        // ->where('is_primary', 0);

        if (!empty($filters['keyword'])) {
            $query = $query->where(function ($query) use ($filters) {
                return $query->where('locale', 'LIKE', '%' . $filters['keyword'] . '%')
                    ->orWhere('name', 'LIKE', '%' . $filters['keyword'] . '%');
            });
        }

        if (isset($filters['status'])) {
            $filters['status'] = !is_array($filters['status']) ? (array) $filters['status'] : $filters['status'];
            $query = $query->whereIn('status', $filters['status']);
        }
        if (isset($filters['publish'])) {
            $query = $query->where('publish', $filters['publish']);
        }

        $sort = 'desc';
        if (!empty($filters['sort'])) {
            $sort = $filters['sort'];
        }

        $limit = !empty($request->limit) ? $request->limit : 10;
        $laguages  = $query->orderBy('pin_at', 'desc')->orderBy('id', $sort)->paginate($limit);

        return response([
            'data' => $laguages
        ]);
    }

    public function getAllLanguages(Request $request)
    {
        $list = config('fa_languages');
        $all = [];
        $store = $request->storeInfo;
        $laguage = LanguageModel::where('store_id', $store->store_id)->pluck('locale')->toArray();

        foreach ($list as $k => $v) {
            $v['is_add'] = 0;
            if (in_array($v['locale'], $laguage)) {
                $v['is_add'] = 1;
            }

            $v['id'] = $v['locale'];
            $all[] = $v;
        }

        return response([
            'data' => $all
        ]);
    }

    public function create(Request $request)
    {
        $list = config('fa_languages');
        $store = $request->storeInfo;
        $lang = [];

        $locales = $request->locales;
        $dataSave = [];
        foreach ($list as $v) {
            if (in_array($v['locale'], $locales)) {
                $lang = $v;
                $data = [
                    'store_id' => $store->store_id,
                    'locale' => $lang['locale'],
                    'name' => $lang['name'],
                    'native_name' => $lang['native_name'],
                    'flag_code' => $lang['flag_code'],
                    'status' => LanguageModel::STATUS_UNTRANSLATED,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $dataSave[] = $data;
            }
        }

        LanguageModel::insert($dataSave);

        return response([
            'data' => $lang
        ]);
    }

    public function remove(Request $request)
    {
        // $list = config('fa_languages');
        $store = $request->storeInfo;

        $locales = $request->locales;
        LanguageModel::whereIn('locale', $locales)->where('store_id', $store->store_id)->where('is_primary', 0)->delete();

        return response([]);
    }

    public function pin(Request $request)
    {
        $store = $request->storeInfo;

        $locales = $request->locales;
        LanguageModel::whereIn('locale', $locales)->where('store_id', $store->store_id)->update(
            [
                'pin_at' => date('Y-m-d H:i:s')
            ]
        );

        return response([]);
    }

    public function unpin(Request $request)
    {
        $store = $request->storeInfo;

        $locales = $request->locales;
        LanguageModel::whereIn('locale', $locales)->where('store_id', $store->store_id)->update(
            [
                'pin_at' => null
            ]
        );

        return response([]);
    }

    public function translate(Request $request)
    {
        $store = $request->storeInfo;

        $locales = $request->locales;
        LanguageModel::whereIn('locale', $locales)->where('store_id', $store->store_id)->update(
            [
                'status' => LanguageModel::STATUS_TRANSLATED
            ]
        );

        return response([]);
    }

    public function publish(Request $request)
    {
        $store = $request->storeInfo;

        $locales = $request->locales;
        LanguageModel::whereIn('locale', $locales)->where('store_id', $store->store_id)->update(
            [
                'publish' => 1
            ]
        );

        return response([]);
    }

    public function unpublish(Request $request)
    {
        $store = $request->storeInfo;

        $locales = $request->locales;
        LanguageModel::whereIn('locale', $locales)->where('store_id', $store->store_id)->update(
            [
                'publish' => 0
            ]
        );

        return response([]);
    }
}
