<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (Auth::check()) {
            $lastActivity = session('last_activity');
            $timeoutMinutes = (int) settings('session_timeout_minutes', 60);
            
            // If last activity is set, check if timeout exceeded
            if ($lastActivity) {
                // Parse if it's a string (though we store timestamp/int usually)
                // Let's store as unix timestamp for simplicity
                $timeSinceLastActivity = now()->timestamp - $lastActivity;
                
                if ($timeSinceLastActivity > ($timeoutMinutes * 60)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Session expired due to inactivity.'], 401);
                    }
                    
                    return redirect()->route('login')->with('error', 'Session expired due to inactivity.');
                }
            }
            
            // Update last activity time
            session(['last_activity' => now()->timestamp]);
        }

        return $next($request);
    }
}
