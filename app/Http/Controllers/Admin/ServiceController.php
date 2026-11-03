<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderServicesRequest;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/services/Index', [
            'services' => Service::ordered()->get(['id', 'name', 'base_price', 'duration_minutes', 'is_active']),
            'trashedServices' => Service::onlyTrashed()->ordered()->get(['id', 'name', 'base_price', 'deleted_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/Form', ['service' => null]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create([...$request->validated(), 'sort_order' => Service::nextSortOrder()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio creado.')]);

        return to_route('admin.services.index');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/Form', [
            'service' => $service->only('id', 'name', 'base_price', 'duration_minutes', 'is_active'),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio actualizado. Las transacciones ya registradas conservan su precio original.')]);

        return to_route('admin.services.index');
    }

    /**
     * Save the order in which services are offered when registering, as dragged in the list.
     */
    public function reorder(ReorderServicesRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('ids') as $position => $id) {
                Service::withTrashed()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Orden actualizado.')]);

        return back();
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio eliminado.')]);

        return to_route('admin.services.index');
    }

    /**
     * Bring back a soft deleted service, keeping its previous settings.
     */
    public function restore(Service $service): RedirectResponse
    {
        $service->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Servicio reactivado.')]);

        return to_route('admin.services.index');
    }
}
