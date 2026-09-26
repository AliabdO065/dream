<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetDashboardLocale;
use Illuminate\Http\Request;

class DashboardLocaleController extends Controller
{
    public function update(Request $request, string $code)
    {
        if (in_array($code, SetDashboardLocale::LOCALES, true)) {
            $request->session()->put('dashboard_locale', $code);
        }

        return back();
    }
}
