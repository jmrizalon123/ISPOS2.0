export interface ShiftReportListRow {
    id: string;
    status: 'open' | 'closed';
    opened_at: string | null;
    closed_at: string | null;
    opening_float: string;
    expected_cash: string;
    closing_float: string | null;
    variance: string | null;
    store_name: string | null;
    store_code: string | null;
    register_name: string | null;
    register_code: string | null;
    cashier_name: string | null;
    transaction_count: number;
    gross_sales: string;
    void_count: number;
}

export interface ShiftReportDetail {
    shift: {
        id: string;
        status: 'open' | 'closed';
        opened_at: string | null;
        closed_at: string | null;
        opening_float: string;
        expected_cash: string;
        closing_float: string | null;
        variance: string | null;
        store: { id: string; store_name: string; store_code: string };
        register: { id: string; name: string; code: string };
        cashier: { id: string; name: string };
    };
    summary: {
        transaction_count: number;
        gross_sales: string;
        void_count: number;
        void_total: string;
        net_sales: string;
    };
    payments: Array<{ payment_method: string; amount: string; count: number }>;
    sales: Array<{
        id: string;
        sale_number: string;
        status: string;
        grand_total: string;
        completed_at: string | null;
        voided_at: string | null;
        customer_name?: string | null;
    }>;
}
