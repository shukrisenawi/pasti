<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AjkProgramController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        return view('ajk-program.index');
    }
}
