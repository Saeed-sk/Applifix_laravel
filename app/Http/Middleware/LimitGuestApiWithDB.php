<?php

namespace App\Http\Middleware;

use App\Models\GuestApiRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LimitGuestApiWithDB
{
    public function handle(Request $request, Closure $next): JsonResponse
    {
        if (auth()->check()) {
            return $next($request);
        }

        $ip       = $request->ip();
        $endpoint = $request->path();
        $now      = Carbon::now();
        $limit    = (int) config('services.limit', 5);

        $response = DB::transaction(function () use (
            $ip, $endpoint, $now, $limit, $next, $request
        ) {
            $record = GuestApiRequest::lockForUpdate()
                ->firstOrCreate(
                    ['ip' => $ip, 'endpoint' => $endpoint],
                    ['request_count' => 0, 'last_request_at' => $now]
                );

            if ($record->last_request_at->diffInHours($now) >= 1) {
                $record->update([
                    'request_count'     => 1,
                    'last_request_at'   => $now,
                ]);
            }
            elseif ($record->request_count >= $limit) {
                return response()->json([
                    'message'      => 'You have reached the limit of free usage. Please register to continue using this service.',
                ], 429);
            }
            else {
                $record->increment('request_count');
                $record->update(['last_request_at' => $now]);
            }

            return $next($request);
        });

        return $response;
    }
}
