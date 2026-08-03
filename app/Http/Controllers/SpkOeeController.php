<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SpkOeeController extends Controller
{
    public function __invoke(): View
    {
        return view('spk-oee');
    }
}
