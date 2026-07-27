<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LanguageController extends Controller
{
    public function switch(Request $request)
    {
        $supported = ['en', 'sw', 'sheng', 'kikuyu', 'luo', 'luhya', 'kalenjin', 'kamba'];
        $language = strtolower($request->input('language', 'en'));

        if (in_array($language, $supported)) {
            // Save to session explicitly
            session(['app_locale' => $language]);
            session()->save(); // Force session persistence before redirect
        }

        return redirect()->back();
    }
}