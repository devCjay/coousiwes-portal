<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(): View
    {
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
