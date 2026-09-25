import { reactive } from 'vue';
import { useI18n } from 'vue-i18n';

export type ModuleKey =
    | 'dashboard'
    | 'categories'
    | 'products'
    | 'brands'
    | 'units'
    | 'taxes'
    | 'priceGroups'
    | 'companies'
    | 'stores'
    | 'salesPlans'
    | 'registers'
    | 'users'
    | 'roles'
    | 'auditLogs'
    | 'stock'
    | 'movements'
    | 'transfers'
    | 'lowStock'
    | 'adjustments'
    | 'suppliers'
    | 'purchaseOrders'
    | 'purchaseReturns'
    | 'customers'
    | 'loyaltyPrograms'
    | 'membershipPlans'
    | 'promotions'
    | 'onlineStoreSettings'
    | 'onlineOrders'
    | 'salesSummary'
    | 'salesRegister'
    | 'productSales'
    | 'shiftReports'
    | 'chartOfAccounts'
    | 'journalEntries'
    | 'trialBalance'
    | 'profitAndLoss'
    | 'balanceSheet'
    | 'apAging'
    | 'vendorBills'
    | 'supplierPayments'
    | 'posDevices'
    | 'posUi'
    | 'appearance';

const moduleNavKeys: Record<ModuleKey, string> = {
    dashboard: 'dashboard',
    categories: 'admin_categories_index',
    products: 'admin_products_index',
    brands: 'admin_brands_index',
    units: 'admin_units_index',
    taxes: 'admin_taxes_index',
    priceGroups: 'admin_price-groups_index',
    companies: 'admin_companies_index',
    stores: 'admin_stores_index',
    salesPlans: 'admin_sales-plans_index',
    registers: 'admin_registers_index',
    users: 'admin_users_index',
    roles: 'admin_roles_index',
    auditLogs: 'admin_audit-logs_index',
    stock: 'admin_inventory_stock_index',
    movements: 'admin_inventory_movements_index',
    transfers: 'admin_inventory_transfers_index',
    lowStock: 'admin_inventory_low-stock',
    adjustments: 'admin_inventory_adjustments_create',
    suppliers: 'admin_suppliers_index',
    purchaseOrders: 'admin_purchase-orders_index',
    purchaseReturns: 'admin_purchase-returns_index',
    customers: 'admin_customers_index',
    loyaltyPrograms: 'admin_loyalty-programs_index',
    membershipPlans: 'admin_membership-plans_index',
    promotions: 'admin_promotions_index',
    onlineStoreSettings: 'admin_online-store-settings_index',
    onlineOrders: 'admin_online-orders_index',
    salesSummary: 'admin_reports_sales-summary_index',
    salesRegister: 'admin_reports_sales-register_index',
    productSales: 'admin_reports_product-sales_index',
    shiftReports: 'admin_reports_shift-reports_index',
    chartOfAccounts: 'admin_chart-of-accounts_index',
    journalEntries: 'admin_journal-entries_index',
    trialBalance: 'admin_accounting_trial-balance_index',
    profitAndLoss: 'admin_accounting_profit-and-loss_index',
    balanceSheet: 'admin_accounting_balance-sheet_index',
    apAging: 'admin_accounting_ap-aging_index',
    vendorBills: 'admin_vendor-bills_index',
    supplierPayments: 'admin_supplier-payments_index',
    posDevices: 'admin_pos-devices_index',
    posUi: 'admin_pos-ui_edit',
    appearance: 'appearance_edit',
};

const moduleSubheaderKeys: Partial<Record<ModuleKey, string>> = {
    categories: 'subheaders.catalogManagement',
    products: 'subheaders.catalogManagement',
    brands: 'subheaders.catalogManagement',
    units: 'subheaders.catalogManagement',
    taxes: 'subheaders.catalogManagement',
    priceGroups: 'subheaders.catalogManagement',
    companies: 'subheaders.organization',
    stores: 'subheaders.organization',
    salesPlans: 'subheaders.organization',
    registers: 'subheaders.pointOfSale',
    users: 'subheaders.administration',
    roles: 'subheaders.administration',
    auditLogs: 'subheaders.securityCompliance',
    stock: 'subheaders.perStoreInventory',
    movements: 'subheaders.inventoryLedger',
    transfers: 'subheaders.inventory',
    lowStock: 'subheaders.inventoryMonitoring',
    adjustments: 'subheaders.manualCorrection',
    suppliers: 'subheaders.purchasing',
    purchaseOrders: 'subheaders.purchasing',
    purchaseReturns: 'subheaders.purchasing',
    customers: 'subheaders.crm',
    loyaltyPrograms: 'subheaders.crm',
    membershipPlans: 'subheaders.crm',
    promotions: 'subheaders.crm',
    onlineStoreSettings: 'subheaders.onlineStore',
    onlineOrders: 'subheaders.onlineStore',
    salesSummary: 'subheaders.reports',
    salesRegister: 'subheaders.reports',
    productSales: 'subheaders.reports',
    shiftReports: 'subheaders.reports',
    chartOfAccounts: 'subheaders.accounting',
    journalEntries: 'subheaders.accounting',
    trialBalance: 'subheaders.accounting',
    profitAndLoss: 'subheaders.accounting',
    balanceSheet: 'subheaders.accounting',
    apAging: 'subheaders.accountsPayable',
    vendorBills: 'subheaders.accountsPayable',
    supplierPayments: 'subheaders.accountsPayable',
    posDevices: 'subheaders.offlineSync',
    posUi: 'subheaders.terminalLayout',
    appearance: 'subheaders.appearanceFineTune',
};

function pageKey(moduleKey: ModuleKey, suffix: string): string {
    return `pages.${moduleKey}.${suffix}`;
}

export function useModulePage(moduleKey: ModuleKey) {
    const { t } = useI18n();
    const navKey = moduleNavKeys[moduleKey];
    const subheaderKey = moduleSubheaderKeys[moduleKey];

    return reactive({
        get header() {
            return t(`nav.${navKey}`);
        },
        get subheader() {
            return subheaderKey ? t(subheaderKey) : '';
        },
        get eyebrow() {
            return t(pageKey(moduleKey, 'eyebrow'));
        },
        get title() {
            return t(pageKey(moduleKey, 'title'));
        },
        get description() {
            return t(pageKey(moduleKey, 'description'));
        },
        get newButton() {
            return t(pageKey(moduleKey, 'newButton'));
        },
        get searchPlaceholder() {
            return t(pageKey(moduleKey, 'searchPlaceholder'));
        },
        get emptyTitle() {
            return t(pageKey(moduleKey, 'emptyTitle'));
        },
        get emptyDescription() {
            return t(pageKey(moduleKey, 'emptyDescription'));
        },
        get emptyDescriptionReadonly() {
            return t(pageKey(moduleKey, 'emptyDescriptionReadonly'));
        },
        get emptyAction() {
            return t(pageKey(moduleKey, 'emptyAction'));
        },
        get showHeader() {
            return t(pageKey(moduleKey, 'showHeader'));
        },
    });
}
