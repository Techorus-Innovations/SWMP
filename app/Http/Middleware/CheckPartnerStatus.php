<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPartnerStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check on logout
        if ($request->routeIs('filament.partner.auth.logout')) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user) {
            $partner = $user->businessPartner;
            if ($partner) {
                if ($partner->status === 'P' && !$request->routeIs('filament.partner.pages.pending-approval')) {
                    return redirect()->route('filament.partner.pages.pending-approval');
                }

                if ($partner->status === 'R' && !$request->routeIs('filament.partner.pages.rejected-request')) {
                    return redirect()->route('filament.partner.pages.rejected-request');
                }
            }
        }

        return $next($request);
    }
}
