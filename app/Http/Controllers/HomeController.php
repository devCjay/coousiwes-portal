<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Support\MaintenanceMode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    public function __invoke(): View|Response
    {
        if (MaintenanceMode::enabled()) {
            return view('pages.maintenance');
        }

        return view('pages.home', [
            'notices' => Schema::hasTable('notices')
                ? Notice::query()
                    ->published()
                    ->orderByDesc('is_pinned')
                    ->latest('published_at')
                    ->limit(6)
                    ->get()
                : collect(),
        ]);
    }
}
