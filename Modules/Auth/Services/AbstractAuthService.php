<?php
namespace Modules\Auth\Services;


abstract class AbstractAuthService
{
    protected $sentry;
    public function __construct() {
        $this->sentry = app('sentry');
    }

}
