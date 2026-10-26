<?php

namespace App\Http\Controllers;

use App\Enums\ClosingPeriod;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        $user = $request->user();
        $today = CarbonImmutable::today();
        $barberId = $user->isAdmin() ? null : $user->id;

        [$weekStart, $weekEnd] = ClosingPeriod::Weekly->range($today);
        [$monthStart, $monthEnd] = ClosingPeriod::Monthly->range($today);

        $recent = Transaction::query()
            ->with('barber:id,name')
            ->when($barberId, fn ($query) => $query->where('barber_id', $barberId))
            ->latest('performed_at')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Transaction $transaction) => (new TransactionResource($transaction))->resolve());

        return Inertia::render($user->isAdmin() ? 'admin/Dashboard' : 'barber/Dashboard', [
            'today' => $reports->summary($today->startOfDay(), $today->endOfDay(), $barberId),
            'week' => $reports->summary($weekStart, $weekEnd, $barberId)['totals'],
            'month' => $reports->summary($monthStart, $monthEnd, $barberId)['totals'],
            'recent' => $recent,
        ]);
    }
}
