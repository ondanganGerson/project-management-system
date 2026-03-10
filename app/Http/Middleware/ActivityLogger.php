<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ActivityLogger Middleware
 *
 * Logs all API requests: user_id, HTTP method, endpoint, IP address,
 * user agent, and timestamp. Stores to both DB and log file.
 */
class ActivityLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->logActivity($request, $response);

        return $response;
    }

    private function logActivity(Request $request, Response $response): void
    {
        try {
            $user   = $request->user();
            $userId = $user?->id;

            // Store in database
            ActivityLog::create([
                'user_id'         => $userId,
                'method'          => $request->method(),
                'endpoint'        => $request->getPathInfo(),
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
                'response_status' => $response->getStatusCode(),
                'timestamp'       => now(),
            ]);

            // Also write to log file
            Log::channel('daily')->info('API Activity', [
                'user_id'   => $userId,
                'method'    => $request->method(),
                'endpoint'  => $request->getPathInfo(),
                'ip'        => $request->ip(),
                'status'    => $response->getStatusCode(),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // Never let logging failure break the request pipeline
            Log::error('ActivityLogger failed: ' . $e->getMessage());
        }
    }
}
