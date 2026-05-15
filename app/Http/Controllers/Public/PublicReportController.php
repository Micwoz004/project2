<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PublicReportController extends Controller
{
    public function index(): View
    {
        return view('public.reports.index');
    }
}
