<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackVisits
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Don't track assets, API calls, or admin routes
        if (!$this->shouldTrack($request)) {
            return $response;
        }

        Visit::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'user_id' => Auth::id(),
            'session_id' => session()->getId()
        ]);

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        // Don't track assets
        if ($request->is('*.js', '*.css', '*.ico', '*.png', '*.jpg', '*.gif', '*.svg')) {
            return false;
        }

        // Don't track API calls
        if ($request->is('api/*')) {
            return false;
        }

        // Don't track admin routes
        if ($request->is('admin/*')) {
            return false;
        }

        return true;
    }
} 