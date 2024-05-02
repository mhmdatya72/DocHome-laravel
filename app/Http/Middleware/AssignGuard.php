<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AssignGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (!Auth::guard('admin')->check()) {
            // إذا لم يكن المستخدم مسجل كمسؤول، يمكنك توجيهه إلى رسالة تنبيه
            return response()->json(['error' => 'You are not registered as an admin.'], 401);
        }
        if (!Auth::guard('caregiver')->check()) {
            // إذا لم يكن المستخدم مسجل كمسؤول، يمكنك توجيهه إلى رسالة تنبيه
            return response()->json(['error' => 'You are not registered as an caregiver.'], 401);
        }else {
            return response()->json(['error' => 'You are not registered as an user.'], 401);
        }
        return $next($request);
    }
}
