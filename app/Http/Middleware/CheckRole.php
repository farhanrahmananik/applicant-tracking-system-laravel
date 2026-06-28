<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roleSlugs = collect($roles)
            ->flatMap(fn (string $role): array => explode(',', $role))
            ->map(fn (string $role): string => trim($role))
            ->filter()
            ->unique()
            ->values()
            ->all();

        abort_unless(
            $request->user()?->hasAnyRole($roleSlugs),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
