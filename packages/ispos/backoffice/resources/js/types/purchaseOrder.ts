import type { AuditableUser } from '@/types';

export interface PurchaseOrderLineRecord {
    id?: string;
    product_id: string;
    line_number?: number;
    ordered_qty: string | number;
    received_qty?: string | number;
    unit_cost: string | number;
    line_total?: string | number;
    notes?: string | null;
    product?: { id: string; sku: string; name: string };
}

export interface PurchaseOrderRecord {
    id: string;
    company_id: string;
    store_id: string;
    supplier_id: string;
    po_number: string;
    status: string;
    order_date: string;
    expected_date: string | null;
    notes: string | null;
    subtotal: string;
    tax_total: string;
    grand_total: string;
    approved_at: string | null;
    received_at: string | null;
    created_at: string;
    updated_at: string;
    supplier?: { id: string; name: string; supplier_code: string };
    store?: { id: string; store_name: string; store_code: string };
    approver?: AuditableUser | null;
    lines?: PurchaseOrderLineRecord[];
}

export interface PurchaseOrderLineForm {
    product_id: string;
    ordered_qty: number | string;
    unit_cost: number | string;
    notes: string;
}

export interface PurchaseOrderFormData {
    store_id: string;
    supplier_id: string;
    order_date: string;
    expected_date: string;
    notes: string;
    lines: PurchaseOrderLineForm[];
}

export function defaultPurchaseOrderLine(): PurchaseOrderLineForm {
    return { product_id: '', ordered_qty: 1, unit_cost: 0, notes: '' };
}

export function defaultPurchaseOrderForm(
    po?: Partial<PurchaseOrderRecord> | null,
    storeId = '',
    supplierId = '',
): PurchaseOrderFormData {
    return {
        store_id: po?.store_id ?? storeId,
        supplier_id: po?.supplier_id ?? supplierId,
        order_date: po?.order_date ?? new Date().toISOString().slice(0, 10),
        expected_date: po?.expected_date ?? '',
        notes: po?.notes ?? '',
        lines: po?.lines?.map((line) => ({
            product_id: line.product_id,
            ordered_qty: line.ordered_qty,
            unit_cost: line.unit_cost,
            notes: line.notes ?? '',
        })) ?? [defaultPurchaseOrderLine()],
    };
}
