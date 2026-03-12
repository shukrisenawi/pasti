<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:ms,en'],
        ]);

        session(['locale' => $data['locale']]);

        if ($request->user()) {
            $request->user()->update(['locale' => $data['locale']]);
        }

        return back();
    }
}
