# Phase 6 — CRM (Customers, Loyalty, Membership, Promotions)

Company-scoped customer master, loyalty programs with point ledger, membership plans, promotional discounts, and POS integration at checkout.

**Status: Complete** — verified September 2026.

## Stack additions
- [x] CRM domain services under `app/Domains/Crm/Services/`
- [x] Eloquent models: `Customer`, `LoyaltyProgram`, `LoyaltyTransaction`, `MembershipPlan`, `CustomerMembership`, `Promotion`
- [x] Policies: `CustomerPolicy`, `LoyaltyProgramPolicy`, `MembershipPlanPolicy`, `PromotionPolicy`

## Database
- [x] `customers` — company-scoped customer master with loyalty balance
- [x] `loyalty_programs` + `loyalty_transactions` — earn/adjust/void reversal ledger
- [x] `membership_plans` + `customer_memberships` — plan benefits and assignments
- [x] `promotions` + `promotion_product` / `promotion_category` pivots
- [x] `sales.customer_id`, `sales.promotion_id`, `sales.loyalty_points_earned`

## Backend
- [x] `CustomerService` — CRUD, POS search
- [x] `LoyaltyProgramService` — program CRUD, default program
- [x] `LoyaltyTransactionService` — earn on sale, void reversal, manual adjust
- [x] `MembershipPlanService` — plan CRUD
- [x] `CustomerMembershipService` — assign/cancel membership
- [x] `PromotionService` — CRUD, activate/cancel, usage tracking
- [x] `PromotionApplicationService` — best active promo + membership discount at POS

## POS integration
- [x] Attach/detach customer on cart session
- [x] Auto-apply best active promotion + membership discount
- [x] Earn loyalty points on completed sale; reverse on void
- [x] Customer picker modal on POS terminal

## HTTP routes
- [x] `admin.customers.*` — customer CRUD + loyalty adjust + membership assign
- [x] `admin.loyalty-programs.*` — loyalty program CRUD
- [x] `admin.membership-plans.*` — membership plan CRUD
- [x] `admin.promotions.*` — promotion CRUD + activate/cancel
- [x] `pos.customers.search`, `pos.cart.customer.*` — POS customer attach

## Frontend (Inertia admin)
- [x] CRM nav section: Customers, Loyalty Programs, Membership Plans, Promotions
- [x] Customer Index/Form with loyalty history and membership assign
- [x] Loyalty / Membership / Promotion Index/Form pages

## Demo data
- [x] Demo Rewards loyalty program (1 pt per ₱1)
- [x] Gold Member plan (5% discount)
- [x] Maria Santos (member, 120 pts) + Juan Cruz
- [x] BEV10 (10% off beverages) + SAVE50 (₱50 off ₱500+)

## Tests
- [x] `CustomerCrudTest`
- [x] `PromotionCheckoutTest` — promo discount + loyalty earn
- [x] `LoyaltyVoidReversalTest` — void reverses earned loyalty points
- [x] `CrmPolicyTest`

## Explicitly out of scope (Phase 7+)
- Points redemption at POS (redeem_value_per_point reserved)
- Customer purchase history UI, email/SMS marketing
- Gift cards, referrals, lead pipeline
- Advanced promo stacking rules

## Verification

```bash
php artisan migrate
php artisan test --filter=Crm
php artisan test
npm run build
```

Manual: login as `cashier@demo.ispos.local` → POS → attach **Maria Santos** → add Cola → checkout → verify discount and loyalty points in **Customers** edit page.
