<?php

declare(strict_types=1);

namespace Modules\Auth\Lib;

use Exception;
use Shopify\Auth\Session;
use Shopify\Auth\SessionStorage;
use Illuminate\Support\Facades\Log;

class DbSessionStorage implements SessionStorage
{
    public function loadSession(string $sessionId): ?Session
    {
        return null;
    }

    public function storeSession(Session $session): bool
    {
        try {
            \App\Facade\SystemCache::mixCachePaginate('sessions','session_datas',[$session->getId() => json_decode(json_encode($session),true)]);
            return true;
        } catch (Exception $err) {
            Log::error("Failed to save session to database: " . $err->getMessage());
            return false;
        }
    }

    public function deleteSession(string $sessionId): bool
    {
        \App\Facade\SystemCache::removeItemsFromHash('session_datas', $sessionId);
        return true;
    }
}
