export interface SalesReportFilters {
    company_id: string;
    store_id: string;
    date_from: string;
    date_to: string;
    status: string;
    search?: string;
}

export interface SalesSummary {
    gross_sales: string;
    discount_total: string;
    tax_total: string;
    net_sales: string;
    transactions: number;
    voided_count: number;
    average_ticket: string;
}

export interface SalesTrendPoint {
    date: string;
    total: string;
    count: number;
}

export interface StoreSalesBreakdown {
    store_id: string;
    store_name: string;
    store_code: string;
    total: string;
    count: number;
}

export interface SalesRegisterRow {
    id: string;
    sale_number: string;
    status: string;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    grand_total: string;
    completed_at: string | null;
    store?: { store_name: string; store_code: string };
    register?: { name: string; code: string };
    user?: { name: string };
    customer?: { first_name: string; last_name: string | null; customer_code: string } | null;
}

export interface ProductSalesRow {
    product_id: string | null;
    sku: string;
    name: string;
    qty_sold: string;
    revenue: string;
}
