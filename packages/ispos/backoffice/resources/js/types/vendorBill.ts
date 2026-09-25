export interface VendorBillRecord {
    id: string;
    company_id: string;
    supplier_id: string;
    purchase_receipt_id: string | null;
    purchase_order_id: string | null;
    bill_number: string;
    bill_date: string;
    due_date: string | null;
    status: string;
    amount_due: string | number;
    amount_paid: string | number;
    notes: string | null;
    supplier?: { id: string; name: string };
    purchase_order?: { id: string; po_number: string };
}

export interface OpenVendorBillRecord {
    id: string;
    bill_number: string;
    bill_date: string;
    due_date: string | null;
    amount_due: string | number;
    amount_paid: string | number;
    status: string;
}
