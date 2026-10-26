<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/services/Index', [
            'services' => Service::ordered()->get(['id', 'name', 'base_price', 'duration_minutes', 'is_active', 'sort_order']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/Form', ['service' => null]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio creado.')]);

        return to_route('admin.services.index');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/Form', [
            'service' => $service->only('id', 'name', 'base_price', 'duration_minutes', 'is_active', 'sort_order'),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio actualizado. Las transacciones ya registradas conservan su precio original.')]);

        return to_route('admin.services.index');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio eliminado.')]);

        return to_route('admin.services.index');
    }
}
