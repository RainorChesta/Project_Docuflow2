<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     */
    public function switch(string $locale, Request $request): RedirectResponse
    {
        if (in_array($locale, ['id', 'en'])) {
            Session::put('locale', $locale);
            $cookie = cookie('locale', $locale, 60 * 24 * 365);
            return redirect()->back()->withCookie($cookie);
        }

        return redirect()->back();
    }
}
