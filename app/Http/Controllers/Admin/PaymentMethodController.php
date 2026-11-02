<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/payment-methods/Index', [
            'paymentMethods' => PaymentMethod::ordered()->get(['id', 'name', 'iva_rate', 'is_active', 'sort_order']),
            'trashedPaymentMethods' => PaymentMethod::onlyTrashed()->ordered()->get(['id', 'name', 'iva_rate', 'deleted_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/payment-methods/Form', ['paymentMethod' => null]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        PaymentMethod::create([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Método de pago creado.')]);

        return to_route('admin.payment-methods.index');
    }

    public function edit(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('admin/payment-methods/Form', [
            'paymentMethod' => $paymentMethod->only('id', 'name', 'iva_rate', 'is_active', 'sort_order'),
        ]);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Método de pago actualizado. Las transacciones ya registradas conservan su comisión original.')]);

        return to_route('admin.payment-methods.index');
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
