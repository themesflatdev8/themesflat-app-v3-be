<?php

namespace Modules\Auth\Http\Controllers;

use App\Facade\SystemCache;
use App\Models\StoreModel;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Services\TAuthService;

class AuthController extends Controller
{
    private $sentry;
    /**
     * TranslateController constructor.
     * @param TaskTranslationService $taskTranslationService
     */
    public function __construct()
    {
        $this->sentry = app('sentry');;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $result = app(TAuthService::class)->verifyRequest($request->all());
            if ($result['status']) {
                return response()->json(['data' => $result['data']]);
            } else {
                return response()->json(['data' => $result['data'], 'message' => @$result['message']], 401);
            }
        } catch (\Exception $ex) {
            $this->sentry->captureException($ex);
            return response()->json(['message' => $ex->getMessage()], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function handleCallback(Request $request)
    {
        $result = app(TAuthService::class)->verifyCallback($request->all());
        return redirect($result);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('auth::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('auth::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
