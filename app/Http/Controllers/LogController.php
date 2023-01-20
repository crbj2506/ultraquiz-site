<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogController extends Controller
{
    protected $log;
    //
    public function __construct(Log $log){
        $this->log = $log;
    }
    public function index(Request $request)
    {
        //
        $logs = $this->log->orderByDesc('created_at')->paginate(200);
        return view('log.index',[ 'logs' => $logs ]);
    }
}