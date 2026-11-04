<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the counselor's desk. Guidance modules (appointments, referrals,
     * cases, follow-ups) are added in Phase 4; until then the desk shows
     * the layout with honest empty states.
     */
    public function __invoke(): Response
    {
        return Inertia::render('counselor/dashboard', [
            'today' => now()->toDateString(),
        ]);
    }
}
