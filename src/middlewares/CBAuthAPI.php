<?php

namespace crocodicstudio\crudbooster\middlewares;

use Closure;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Http\Request;

class CBAuthAPI
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        CRUDBooster::authAPI();

        return $next($request);
    }
}
