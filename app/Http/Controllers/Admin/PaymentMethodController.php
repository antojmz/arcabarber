<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Http\Requests\Admin\ReorderPaymentMethodsRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/payment-methods/Index', [
            'paymentMethods' => PaymentMethod::ordered()->get(['id', 'name', 'iva_rate', 'is_active']),
            'trashedPaymentMethods' => PaymentMethod::onlyTrashed()->ordered()->get(['id', 'name', 'iva_rate', 'deleted_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/payment-methods/Form', ['paymentMethod' => null]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        PaymentMethod::create([...$request->validated(), 'sort_order' => PaymentMethod::nextSortOrder()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Método de pago creado.')]);

        return to_route('admin.payment-methods.index');
    }

    public function edit(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('admin/payment-methods/Form', [
            'paymentMethod' => $paymentMethod->only('id', 'name', 'iva_rate', 'is_active'),
        ]);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Método de pago actualizado. Las transacciones ya registradas conservan su comisión original.')]);

        return to_route('admin.payment-methods.index');
    }

    /**
     * Save the order in which payment methods are offered when registering, as dragged in the list.
     */
    public function reorder(ReorderPaymentMethodsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('ids') as $position => $id) {
                PaymentMethod::withTrashed()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Orden actualizado.')]);

        return back();
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Método de pago eliminado.')]);

        return to_route('admin.payment-methods.index');
    }

    /**
     * Bring back a soft deleted payment method, keeping its previous settings.
     */
    public function restore(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Método de pago reactivado.')]);

        return to_route('admin.payment-methods.index');
    }
}
