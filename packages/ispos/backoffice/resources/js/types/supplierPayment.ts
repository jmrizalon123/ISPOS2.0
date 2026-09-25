import type { OpenVendorBillRecord } from '@/types/vendorBill';

export type { OpenVendorBillRecord };

export interface SupplierPaymentAllocationRecord {
    id: string;
    vendor_bill_id: string;
    amount: string | number;
    vendor_bill?: { id: string; bill_number: string };
}

export interface SupplierPaymentRecord {
    id: string;
    company_id: string;
    supplier_id: string;
    payment_number: string;
    payment_date: string;
    payment_method: string;
    amount: string | number;
    reference: string | null;
    notes: string | null;
    supplier?: { id: string; name: string };
    allocations?: SupplierPaymentAllocationRecord[];
}

export interface SupplierPaymentAllocationFormData {
    vendor_bill_id: string;
    amount: string;
}

export interface SupplierPaymentFormData {
    company_id: string;
    supplier_id: string;
    payment_date: string;
    payment_method: string;
    amount: string;
    reference: string;
    notes: string;
    allocations: SupplierPaymentAllocationFormData[];
}

export function billBalance(bill: OpenVendorBillRecord): string {
    const due = Number(bill.amount_due);
    const paid = Number(bill.amount_paid);
    return (due - paid).toFixed(4);
}

export function defaultSupplierPaymentForm(
    companyId = '',
    supplierId = '',
    openBills: OpenVendorBillRecord[] = [],
): SupplierPaymentFormData {
    return {
        company_id: companyId,
        supplier_id: supplierId,
        payment_date: new Date().toISOString().slice(0, 10),
        payment_method: 'cash',
        amount: '',
        reference: '',
        notes: '',
        allocations: openBills.map((bill) => ({
            vendor_bill_id: bill.id,
            amount: billBalance(bill),
        })),
    };
}
