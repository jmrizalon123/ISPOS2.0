<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\SupplierPaymentService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreSupplierPaymentRequest;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierPaymentController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected SupplierPaymentService $supplierPaymentService)
    {
        $this->authorizeResource(SupplierPayment::class, 'supplier_payment');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Accounting/SupplierPayments/Index', [
            'payments' => $this->supplierPaymentService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
            ),
            'companies' => $this->companyOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);
        $companyId = $ctx['companyId'] ?? $request->user()->company_id;
        $supplierId = $request->string('supplier_id')->toString() ?: null;

        $suppliersQuery = Supplier::query()->orderBy('name');
        if ($request->user()->hasGlobalOrganizationAccess()) {
            if ($companyId) {
                $suppliersQuery->where('company_id', $companyId);
            }
        } else {
            $suppliersQuery->where('company_id', $request->user()->company_id);
        }

        return Inertia::render('Admin/Accounting/SupplierPayments/Form', [
            'payment' => null,
            'companies' => $this->companyOptions($request),
            'suppliers' => $suppliersQuery->get(['id', 'name', 'company_id']),
            'openBills' => $supplierId
                ? $this->supplierPaymentService->openBillsForSupplier($request->user(), $supplierId, $companyId)
                : collect(),
            'filters' => [
                'company_id' => $companyId ?? '',
                'supplier_id' => $supplierId ?? '',
            ],
        ]);
    }

    public function store(StoreSupplierPaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $supplier = Supplier::query()->findOrFail($validated['supplier_id']);

        $payment = $this->supplierPaymentService->create(
            $request->user(),
            $supplier,
            [
                'company_id' => $validated['company_id'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
            $validated['allocations'],
        );

        return redirect()->route('admin.supplier-payments.index')->with('success', "Payment {$payment->payment_number} recorded.");
    }
}
