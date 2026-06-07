<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;

class CheckAdminAccess
{
    public function __construct(
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Ensures the user is:
     *   1. Authenticated
     *   2. Not suspended
     *   3. Has an admin role
     *
     * Sets the `admin_session` flag on success.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ── 1. Authentication check ──────────────────────────────────
        $user = $request->user();

        if (is_null($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error'   => 'login_required',
                ], 401);
            }

            return redirect()->guest(
                route('login', ['intended' => $request->fullUrl()])
            );
        }

        // ── 2. Suspension check ──────────────────────────────────────
        if (method_exists($user, 'isSuspended') && $user->isSuspended()) {
            $this->logUnauthorizedAttempt($user, $request, 'suspended_access');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been suspended.',
                    'error'   => 'account_suspended',
                    'reason'  => $user->suspension_reason ?? null,
                ], 403);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has been suspended. Contact an administrator for assistance.');
        }

        // ── 3. Admin role check ──────────────────────────────────────
        $isAdmin = $this->resolveAdminStatus($user);

        if (! $isAdmin) {
            $this->logUnauthorizedAttempt($user, $request, 'insufficient_role');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden. Admin access required.',
                    'error'   => 'admin_access_required',
                ], 403);
            }

            abort(403, 'You do not have permission to access the admin dashboard.');
        }

        // ── 4. Set admin session flag ────────────────────────────────
        if ($request->hasSession()) {
            $request->session()->put('admin_session', true);
            $request->session()->put('admin_session_started_at', now()->toIso8601String());
        }

        return $next($request);
    }

    /**
     * Determine whether the user qualifies as an admin.
     *
     * Checks (in order): super-admin email list → isAdmin() method → is_admin attribute.
     */
    protected function resolveAdminStatus(mixed $user): bool
    {
        // Super-admin email whitelist always passes.
        $superAdmins = config('admin-dashboard.authorization.super_admins', []);
        if (in_array($user->email, $superAdmins, true)) {
            return true;
        }

        // Delegate to the model if it has an isAdmin() method (from HasAdminAccess trait).
        if (method_exists($user, 'isAdmin')) {
            return (bool) $user->isAdmin();
        }

        // Fallback: check a raw `is_admin` column.
        if (isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        return false;
    }

    /**
     * Log an unauthorized access attempt to the audit log.
     */
    protected function logUnauthorizedAttempt(mixed $user, Request $request, string $reason): void
    {
        try {
            $this->auditLogger->log(
                action: 'unauthorized_access',
                description: sprintf(
                    'Unauthorized admin access attempt by %s — reason: %s',
                    $user->email ?? 'unknown',
                    $reason,
                ),
                context: [
                    'user_id' => $user->getKey(),
                    'reason'  => $reason,
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('[AdminDashboard] Failed to log unauthorized access attempt.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
