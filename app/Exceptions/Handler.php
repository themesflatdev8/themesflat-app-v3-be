<?php

namespace App\Exceptions;

use Exception;
use Firebase\JWT\ExpiredException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpStatusCode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }


    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $statusCode = HttpStatusCode::HTTP_BAD_REQUEST;
        $errors = [];
        // dd($exception);

        if (str_contains($exception->getFile(), 'framework/src/Illuminate/Auth/Middleware/Authenticate.php')) {
            return redirect($exception->redirectTo());
        }

        switch (true) {
            case $exception instanceof ValidationException:
                $errors = $exception->errors();
                $statusCode = HttpStatusCode::HTTP_UNPROCESSABLE_ENTITY;
                break;
            case $exception instanceof NotFoundHttpException:
            case $exception instanceof MethodNotAllowedHttpException:
            case $exception instanceof AccessDeniedHttpException:
            case $exception instanceof ModelNotFoundException:
                $statusCode = HttpStatusCode::HTTP_NOT_FOUND;
                break;
            case $exception instanceof AuthorizationException:
                $statusCode = HttpStatusCode::HTTP_UNAUTHORIZED;
                break;
            case $exception instanceof ThrottleRequestsException:
                $statusCode = HttpStatusCode::HTTP_TOO_MANY_REQUESTS;
                break;
            case $exception instanceof ExpiredException:
                $statusCode = HttpStatusCode::HTTP_FORBIDDEN;
                break;
            case $exception instanceof HttpException:
                $statusCode = $exception->getStatusCode();
                break;
            case $exception instanceof Exception:
                $statusCode = $exception->getCode();
                break;
            default:
                break;
        }

        if ($statusCode == 0 || $statusCode == "42S22") {
            $statusCode = 500;
        }

        $response = [
            'errors' => $errors,
            'message' => $exception->getMessage(),
        ];

        if (env('APP_ENV') === 'local') {
            $response['file'] = $exception->getFile() . ': ' . $exception->getLine();
        }

        return response()->json($response, $statusCode);
    }
}
