<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\Access\AuthorizationException;

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
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic if needed
        });

        // Render detailed API error responses
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Get environment
                $debug = config('app.debug');

                // Get status code
                $status = 500;

                if ($e instanceof AuthenticationException) {
                    $status = 401;
                } elseif ($e instanceof AuthorizationException) {
                    $status = 403;
                } elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    $status = 404;
                } elseif ($e instanceof ValidationException) {
                    return $this->convertValidationExceptionToResponse($e, $request);
                } elseif ($e instanceof HttpException) {
                    $status = $e->getStatusCode();
                }

                // Basic error response
                $response = [
                    'message' => $e->getMessage() ?: 'Server Error',
                    'status_code' => $status
                ];

                // Add detailed information if in debug mode
                if ($debug) {
                    $response['debug'] = [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'class' => get_class($e),
                        'trace' => collect($e->getTrace())->map(function ($trace) {
                            return [
                                'file' => $trace['file'] ?? null,
                                'line' => $trace['line'] ?? null,
                                'function' => $trace['function'] ?? null,
                                'class' => $trace['class'] ?? null,
                            ];
                        })->take(10)->all()
                    ];
                }

                return response()->json($response, $status);
            }

            return null;
        });
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($e->response) {
            return $e->response;
        }

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $e->validator->errors()->getMessages(),
            'status_code' => 422
        ], 422);
    }
}