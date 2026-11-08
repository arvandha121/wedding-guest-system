<?php

declare(strict_types=1);

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('receptionist.dashboard', [
            'user' => $user,
            'lastLogin' => $user->last_login_at
                ?->timezone(config('wgs.display_timezone'))
                ->format('d/m/Y H:i'),
        ]);
    }
}
