export interface PurchaseReturnLineRecord {
    id?: string;
    product_id: string;
    purchase_order_line_id?: string | null;
    qty: string | number;
    unit_cost: string | number;
    line_total?: string | number;
    product?: { id: string; sku: string; name: string };
}

export interface PurchaseReturnRecord {
    id: string;
    company_id: string;
    store_id: string;
    supplier_id: string;
    purchase_order_id: string | null;
    return_number: string;
    status: string;
    reason: string;
    notes: string | null;
    posted_at: string | null;
    created_at: string;
    updated_at: string;
    supplier?: { id: string; name: string; supplier_code: string };
    store?: { id: string; store_name: string; store_code: string };
    purchase_order?: { id: string; po_number: string } | null;
    lines?: PurchaseReturnLineRecord[];
}

export interface PurchaseReturnLineForm {
    product_id: string;
    qty: number | string;
    unit_cost: number | string;
    purchase_order_line_id: string;
}

export interface PurchaseReturnFormData {
    store_id: string;
    supplier_id: string;
    purchase_order_id: string;
    reason: string;
    notes: string;
    lines: PurchaseReturnLineForm[];
}

export function defaultPurchaseReturnLine(): PurchaseReturnLineForm {
    return { product_id: '', qty: 1, unit_cost: 0, purchase_order_line_id: '' };
}

export function defaultPurchaseReturnForm(
    ret?: Partial<PurchaseReturnRecord> | null,
    storeId = '',
    supplierId = '',
): PurchaseReturnFormData {
    return {
        store_id: ret?.store_id ?? storeId,
        supplier_id: ret?.supplier_id ?? supplierId,
        purchase_order_id: ret?.purchase_order_id ?? '',
        reason: ret?.reason ?? '',
        notes: ret?.notes ?? '',
        lines: ret?.lines?.map((line) => ({
            product_id: line.product_id,
            qty: line.qty,
            unit_cost: line.unit_cost,
            purchase_order_line_id: line.purchase_order_line_id ?? '',
        })) ?? [defaultPurchaseReturnLine()],
    };
}
