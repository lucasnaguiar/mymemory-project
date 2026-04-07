<?php

use App\Exceptions\AuthorizationException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ResourceNotFoundException $e) {
            return ApiResponse::error($e->getMessage(), 404, $e->getErrorCode());
        });

        $exceptions->render(function (AuthorizationException $e) {
            return ApiResponse::error($e->getMessage(), 403, $e->getErrorCode());
        });

        $exceptions->render(function (BusinessRuleException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->getErrorCode());
        });

        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed.');
        });

        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::error('Unauthenticated.', 401, 'unauthenticated');
        });
    })->create();
