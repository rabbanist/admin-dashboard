<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Yourvendor\AdminDashboard\Contracts\AuditLoggerInterface;

class LogAdminActivity
{
    /**
     * The start time of the request.
     */
    protected float $startTime;

    public function __construct(
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->startTime = microtime(true);

        return $next($request);
    }

    /**
     * Perform any tasks after the response has been sent to the browser.
     *
     * This terminate-phase pattern ensures the audit logging is non-blocking.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Only log if the feature is enabled and a user is authenticated.
        if (! config('admin-dashboard.features.activity_log', true) || ! $request->user()) {
            return;
        }

        try {
            $this->recordActivity($request, $response, $this->startTime ?? microtime(true));
        } catch (\Throwable $e) {
            Log::warning('[AdminDashboard] Activity logging failed.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record the admin activity to the audit log.
     */
    protected function recordActivity(Request $request, Response $response, float $startTime): void
    {
        $user        = $request->user();
        $route       = $request->route();
        $duration    = round((microtime(true) - $startTime) * 1000, 2);
        $action      = $this->resolveAction($request);
        $routeName   = $route?->getName();
        $routeAction = $route?->getActionName();

        // 1. Identify the main model from route parameters.
        $model = null;
        if ($route && ! empty($route->parameters())) {
            foreach ($route->parameters() as $param) {
                if ($param instanceof Model) {
                    $model = get_class($param) . '#' . $param->getKey();
                    break;
                }
            }
        }

        // 2. Identify the changes (inputs without sensitive fields).
        $changes = [];
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $changes = $request->except(['password', 'password_confirmation', '_token', '_method']);
        }

        // Build a rich context payload matching all requirements.
        $context = [
            'user'         => $user->email ?? 'unknown',
            'action'       => $action,
            'model'        => $model,
            'changes'      => $changes,
            'timestamp'    => now()->toIso8601String(),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'route_name'   => $routeName,
            'route_action' => $routeAction,
            'status_code'  => $response->getStatusCode(),
            'duration_ms'  => $duration,
        ];

        // Capture all route parameters for detail.
        if ($route && ! empty($route->parameters())) {
            $context['route_params'] = collect($route->parameters())
                ->map(fn ($param) => $param instanceof Model
                    ? get_class($param) . '#' . $param->getKey()
                    : $param
                )
                ->toArray();
        }

        $this->auditLogger->log(
            action: $action,
            description: sprintf(
                '%s %s %s [%d] (%sms)',
                $user->email ?? 'unknown',
                $request->method(),
                $request->path(),
                $response->getStatusCode(),
                $duration,
            ),
            context: $context,
        );
    }

    /**
     * Resolve a human-readable action name from the request.
     */
    protected function resolveAction(Request $request): string
    {
        // Try to derive from the route name: "admin.users.update" → "users.update"
        $routeName = $request->route()?->getName();

        if ($routeName) {
            // Strip the admin prefix.
            $action = preg_replace('/^admin\./', '', $routeName);

            if ($action && $action !== $routeName) {
                return $action;
            }
        }

        // Fallback: map HTTP method to action verb.
        return match ($request->method()) {
            'GET'    => 'view',
            'POST'   => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default  => 'access',
        };
    }
}
