<?php

namespace crocodicstudio\crudbooster\middlewares;

use Closure;
use crocodicstudio\crudbooster\helpers\CRUDBooster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CBBackend
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
        $admin_path = config('crudbooster.ADMIN_PATH') ?: 'admin';

        if (CRUDBooster::myId() == '') {
            $url = url($admin_path . '/login');

            return redirect($url)->with('message', cbLang('not_logged_in'));
        }
        if (CRUDBooster::isLocked()) {
            $url = url($admin_path . '/lock-screen');

            return redirect($url);
        }
        if ($request->url() == CRUDBooster::adminPath('')) {
            $menus = DB::table('cms_menus')->whereRaw("cms_menus.id IN (select id_cms_menus from cms_menus_privileges where id_cms_privileges = '" . CRUDBooster::myPrivilegeId() . "')")->where('is_dashboard', 1)->where('is_active', 1)->first();
            if ($menus) {
                if ($menus->type == 'Statistic') {
                    return redirect()->action('\crocodicstudio\crudbooster\controllers\StatisticBuilderController@getDashboard');
                } elseif ($menus->type == 'Module') {
                    $module = CRUDBooster::first('cms_moduls', ['path' => $menus->path]);
                    return redirect()->action($module->controller . '@getIndex');
                } elseif ($menus->type == 'Route') {
                    $action = str_replace("Controller", "Controller@", $menus->path);
                    $action = str_replace(['Get', 'Post'], ['get', 'post'], $action);
                    return redirect()->action($action);
                } elseif ($menus->type == 'Controller & Method') {
                    return redirect()->action($menus->path);
                } elseif ($menus->type == 'URL') {
                    return redirect($menus->path);
                }
            }
        }
        return $next($request);
    }
}
