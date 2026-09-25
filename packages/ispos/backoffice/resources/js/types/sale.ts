export interface PosCategory {
    id: string;
    name: string;
}

export interface PosVariant {
    id: string;
    name: string;
    sku: string;
    pos_unit_price: string;
}

export interface PosModifierOption {
    id: string;
    name: string;
    option_code: string;
    price_adjustment: string;
    is_default: boolean;
}

export interface PosModifierGroup {
    id: string;
    name: string;
    group_code: string;
    selection_type: 'single' | 'multiple';
    is_required: boolean;
    min_selections: number;
    max_selections: number | null;
    options: PosModifierOption[];
}

export interface PosComponent {
    id: string;
    component_product_id: string;
    name: string;
    quantity: string;
    is_optional: boolean;
}

export interface PosProduct {
    id: string;
    sku: string;
    name: string;
    image: string | null;
    product_type: string;
    category_id: string | null;
    category: { id: string; name: string } | null;
    has_variants: boolean;
    has_modifiers: boolean;
    has_components: boolean;
    pos_unit_price: string;
    matched_variant_id?: string | null;
    variants: PosVariant[];
    modifier_groups: PosModifierGroup[];
    components: PosComponent[];
}

export interface CartModifier {
    product_modifier_group_id: string;
    product_modifier_option_id: string;
    modifier_group_name: string;
    option_name: string;
    price_adjustment: string;
}

export interface CartComponent {
    product_component_id: string;
    component_product_id: string;
    component_name: string;
    quantity: string;
    is_optional: boolean;
    included: boolean;
}

export interface CartLine {
    key: string;
    product_id: string;
    product_variant_id?: string | null;
    sku: string;
    name: string;
    qty: number;
    base_unit_price: string;
    unit_price: string;
    line_subtotal: string;
    tax_amount: string;
    line_total: string;
    modifiers: CartModifier[];
    components: CartComponent[];
}

export interface CartCustomer {
    id: string;
    customer_code: string;
    name: string;
    loyalty_points: string;
    membership?: { id: string; name: string; plan_code: string } | null;
}

export interface CartPromotion {
    id: string;
    promo_code: string;
    name: string;
}

export interface CartSummary {
    lines: CartLine[];
    subtotal: string;
    tax_total: string;
    discount_total: string;
    membership_discount?: string;
    promotion_discount?: string;
    grand_total: string;
    customer?: CartCustomer | null;
    promotion?: CartPromotion | null;
}
