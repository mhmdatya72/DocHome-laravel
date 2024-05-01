<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            // إذا لم يكن المستخدم مسجل كمسؤول، يمكنك توجيهه إلى رسالة تنبيه
            return response()->json(['error' => 'You are not registered as an admin.'], 401);
        }

        return $next($request);
    }
}
