<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class CBHook extends Controller
{

    /*
    | --------------------------------------
    | Please note that you should re-login to see the session work
    | --------------------------------------
    |
    */
    public function afterLogin()
    {

    }
}