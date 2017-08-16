<?php

namespace App\Http\Controllers;

use App\Models\VpsProvision;
use Illuminate\Http\Request;

class VpsCheckStatusController extends Controller
{
    public function index()
    {
        $vpsProvisions = VpsProvision::get();

        return view('reports.vps-status.index', compact('vpsProvisions'));
    }

    public function ping(Request $request)
    {
        $ip = $request->get('ip');

        if(ping($ip) == 1)
            return 'active';
        else
            return 'inactive';
    }
}
