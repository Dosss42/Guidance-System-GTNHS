<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to the given role slugs, e.g. `role:administrator`.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = $request->user()?->role?->slug;

        $allowed = collect($roles)
            ->map(fn (string $role): ?RoleSlug => RoleSlug::tryFrom($role))
            ->contains($userRole);

        abort_unless($userRole !== null && $allowed, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
