<?php

use App\Support\Events\EventLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'perms' => App\Http\Middleware\CheckPermissions::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Convert failed write-form submissions into structured audit entries.
        $exceptions->report(function (ValidationException $exception): void {
            if (app()->runningInConsole()) {
                return;
            }

            $request = request();
            $method = strtoupper((string) $request->method());
            if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                return;
            }

            $route = $request->route();
            $routeName = (string) ($route?->getName() ?? '');
            $routeUri = (string) ($route?->uri() ?? $request->path());

            // Map route namespaces to service-specific failed-form event types.
            $typeName = match (true) {
                str_starts_with($routeName, 'content.') => 'content_form_failed',
                str_starts_with($routeName, 'module.'), str_starts_with($routeName, 'modules.') => 'module_form_failed',
                str_starts_with($routeName, 'profile.'), str_starts_with($routeName, 'profiles.') => 'profile_form_failed',
                str_starts_with($routeName, 'screen.'),
                str_starts_with($routeName, 'screens.'),
                str_starts_with($routeName, 'room.'),
                str_starts_with($routeName, 'building.') => 'screen_form_failed',
                str_starts_with($routeName, 'users.') => 'user_form_failed',
                str_starts_with($routeName, 'role.'), str_starts_with($routeName, 'roles.') => 'role_form_failed',
                default => 'operation_failed',
            };

            $endpoint = $routeName !== '' ? $routeName : $routeUri;
            $sensitive = ['password', 'password_confirmation', 'current_password'];
            // Store validation messages but only the submitted key names, not sensitive values.
            $errors = collect($exception->errors())
                ->map(fn ($messages) => collect((array) $messages)
                    ->map(fn ($message) => (string) $message)
                    ->values()
                    ->all())
                ->all();

            EventLogger::record(
                typeName: $typeName,
                message: 'Form submission failed validation for "' . $endpoint . '".',
                context: [
                    'action' => 'validation.failed',
                    'route_name' => $routeName !== '' ? $routeName : null,
                    'route_uri' => $routeUri,
                    'method' => $method,
                    'input_keys' => array_values(array_keys($request->except($sensitive))),
                    'errors' => $errors,
                ],
                userId: $request->user()?->id,
            );
        });
    })->create();
