<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;

use Rabbanist\AdminDashboard\Exceptions\AdminDashboardException;

class CheckPrivilege
{
    public function __construct(
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware('admin.privilege:update.users')
     *   ->middleware('admin.privilege:update.users,delete.users')  // any of these
     *
     * @param  string  ...$privileges  One or more privilege slugs (comma-separated in route definition).
     */
    public function handle(Request $request, Closure $next, string ...$privileges): Response
    {
        $user = $request->user();

        if (is_null($user)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        // Super-admins bypass all privilege checks.
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if the user has *any* of the required privileges.
        $hasPrivilege = false;

        if (method_exists($user, 'hasPrivilege')) {
            foreach ($privileges as $privilege) {
                if ($user->hasPrivilege($privilege)) {
                    $hasPrivilege = true;
                    break;
                }
            }
        }

        if (! $hasPrivilege) {
            $this->logUnauthorizedAccess($user, $request, $privileges);

            if ($request->expectsJson()) {
                return response()->json([
                    'message'    => 'Forbidden. You do not have the required privilege.',
                    'error'      => 'insufficient_privilege',
                    'required'   => $privileges,
                ], 403);
            }

            throw AdminDashboardException::unauthorized(sprintf(
                'You do not have the required privilege: %s',
                implode(' or ', $privileges)
            ));
        }

        return $next($request);
    }

    /**
     * Log the unauthorized access attempt.
     */
    protected function logUnauthorizedAccess(mixed $user, Request $request, array $privileges): void
    {
        try {
            $this->auditLogger->log(
                action: 'privilege_denied',
                description: sprintf(
                    'User %s denied access — missing privilege: %s',
                    $user->email ?? 'unknown',
                    implode(', ', $privileges),
                ),
                context: [
                    'user_id'              => $user->getKey(),
                    'required_privileges'  => $privileges,
                    'url'                  => $request->fullUrl(),
                    'method'               => $request->method(),
                    'route'                => $request->route()?->getName(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('[AdminDashboard] Failed to log privilege denial.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
