<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentShop
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            $headerShopId = $request->header('X-Shop-Id');
            app()->instance('current_shop_id', $headerShopId ? (int) $headerShopId : null);
        } else {
            app()->instance('current_shop_id', $user->shop_id);
        }

        return $next($request);
    }
}
