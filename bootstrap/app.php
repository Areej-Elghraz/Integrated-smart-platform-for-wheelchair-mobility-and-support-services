<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            // 'authorized' => \App\Http\Middleware\AdminMiddleware::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
        //     if ($request->expectsJson()) {
        //         if ($e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
        //             /** @var \Illuminate\Database\Eloquent\ModelNotFoundException $prev */
        //             $prev = $e->getPrevious();
        //             $model = __('messages.resources.' . strtolower(class_basename($prev->getModel())) . '.singular');
        //             return response()->json([
        //                 'message' => __('messages.404_not_found', ['model' => $model]),

        //             ], 404);
        //         }

        //         return response()->json([
        //             'message' => __('messages.404_not_found', ['model' => __('messages.resources.resource.singular')]),
        //         ], 404);
        //     }
        // });

        // // Validation errors
        // $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => collect($e->errors())->flatten()->first(),
        //             'errors' => $e->errors(),
        //         ], 422);
        //     }
        // });

        // // Unauthorized
        // $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('auth.unauthorized'),
        //         ], 401);
        //     }
        // });

        // $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('auth.forbidden_action'),
        //         ], 403);
        //     }
        // });

        // $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.method_not_allowed'),
        //         ], 405);
        //     }
        // });

        // $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.too_many_requests'),
        //         ], 429);
        //     }
        // });

        // $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.database_error'),
        //         ], 500);
        //     }
        // });

        // $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => $e->getMessage() ?: __('messages.http_error'),
        //         ], $e->getStatusCode());
        //     }
        // });

        // // // Model not found
        // // $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
        // //     if ($request->expectsJson()) {
        // //         return response()->json([
        // //             'status' => 'error',
        // //             'message' => 'Resource not found.',
        // //         ], 404);
        // //     }
        // // });

        // $exceptions->render(function (\Symfony\Component\HttpFoundation\File\Exception\FileException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.file_error'),
        //         ], 500);
        //     }
        // });

        // $exceptions->render(function (\GuzzleHttp\Exception\RequestException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.external_api_error'),
        //         ], 502);
        //     }
        // });

        // $exceptions->render(function (\JsonException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.invalid_json'),
        //         ], 400);
        //     }
        // });

        // $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('auth.csrf_token_mismatch'),
        //         ], 419);
        //     }
        // });

        // // Invalid ability (Sanctum)
        // $exceptions->render(function (\Exception $e, $request) {
        //     if ($e instanceof \Laravel\Sanctum\Exceptions\MissingAbilityException) {
        //         return response()->json([
        //             'message' => __('auth.invalid_ability'),
        //         ], 403);
        //     }
        // });

        // $exceptions->render(function (\Laravel\Sanctum\Exceptions\MissingScopeException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('auth.invalid_scope'),
        //         ], 403);
        //     }
        // });

        // $exceptions->render(function (\Throwable $e, $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'message' => __('messages.internal_server_error'),
        //         ], 500);
        //     }
        // });
    })->create();
