<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /** Send each user to the right place after login. */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $client = $user->clients()->orderBy('name')->first();
        abort_unless($client, 403, 'Your account is not linked to any client yet. Ask an administrator to add you.');

        return redirect()->route('manage.dashboard', $client);
    }
}
