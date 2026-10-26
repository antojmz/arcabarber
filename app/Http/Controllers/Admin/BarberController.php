<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BarberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BarberController extends Controller
{
    public function index(): Response
    {
        $monthStart = now()->startOfMonth();

        return Inertia::render('admin/barbers/Index', [
            'barbers' => User::barbers()
                ->withCount(['transactions as month_services_count' => fn ($query) => $query->valid()->where('performed_at', '>=', $monthStart)])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'commission_rate', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/barbers/Form', ['barber' => null]);
    }

    public function store(BarberRequest $request): RedirectResponse
    {
        $barber = new User($request->safe()->except('password'));
        $barber->password = $request->validated('password');
        $barber->role = UserRole::Barber;
        $barber->email_verified_at = now();
        $barber->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Barbero creado.')]);

        return to_route('admin.barbers.index');
    }

    public function edit(User $barber): Response
    {
        return Inertia::render('admin/barbers/Form', [
            'barber' => $barber->only('id', 'name', 'email', 'phone', 'commission_rate', 'is_active'),
        ]);
    }

    public function update(BarberRequest $request, User $barber): RedirectResponse
    {
        $barber->fill($request->safe()->except('password'));

        if ($request->filled('password')) {
            $barber->password = $request->validated('password');
        }

        if ($barber->isDirty('email')) {
            $barber->email_verified_at = now();
        }

        $barber->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Barbero actualizado.')]);

        return to_route('admin.barbers.index');
    }
}
