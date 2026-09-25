<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use App\Domains\OnlineStore\Services\OnlineOrderService;
use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Models\Customer;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontCheckoutController extends Controller
{
    public function __construct(
        protected OnlineStoreSettingService $settings,
        protected OnlineOrderService $orders,
    ) {}

    public function create(string $slug): Response
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        $store = $setting->store;
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return Inertia::render('Storefront/Checkout', [
            'setting' => [
                'slug' => $setting->slug,
                'storefront_name' => $setting->storefront_name,
                'primary_color' => $setting->primary_color ?: '#0f766e',
                'accent_color' => $setting->accent_color ?: '#f59e0b',
                'logo_url' => $store->logo_url ?: $setting->logo_url,
                'accept_pickup' => $setting->accept_pickup,
                'accept_delivery' => $setting->accept_delivery,
                'accept_dine_in' => $setting->accept_dine_in,
                'min_order_amount' => $setting->min_order_amount,
                'delivery_fee' => $setting->delivery_fee,
                'free_delivery_threshold' => $setting->free_delivery_threshold,
                'payment_methods' => $setting->payment_methods ?: ['cod', 'pay_at_store'],
                'preparation_minutes' => $setting->preparation_minutes,
            ],
            'store' => [
                'store_name' => $store->store_name,
                'currency' => $store->currency ?: 'PHP',
                'store_category' => $store->store_category,
                'logo_url' => $store->logo_url,
            ],
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->displayName(),
                'email' => $customer->email,
                'phone' => $customer->phone ?: $customer->mobile,
                'address_line_1' => $customer->address_line_1,
                'city' => $customer->city,
                'province' => $customer->province,
                'postal_code' => $customer->postal_code,
            ],
            'acceptingOrders' => $setting->isAcceptingOrdersNow(),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        /** @var Customer $customer */
        $customer = auth('customer')->user();
        $allowedPayments = $setting->payment_methods ?: ['cod', 'pay_at_store'];

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:50'],
            'fulfillment_type' => ['required', Rule::in(['pickup', 'delivery', 'dine_in'])],
            'payment_method' => ['required', Rule::in($allowedPayments)],
            'delivery_address_line_1' => ['nullable', 'required_if:fulfillment_type,delivery', 'string', 'max:255'],
            'delivery_address_line_2' => ['nullable', 'string', 'max:255'],
            'delivery_barangay' => ['nullable', 'string', 'max:100'],
            'delivery_city' => ['nullable', 'required_if:fulfillment_type,delivery', 'string', 'max:100'],
            'delivery_province' => ['nullable', 'string', 'max:100'],
            'delivery_postal_code' => ['nullable', 'string', 'max:20'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string'],
            'lines.*.product_variant_id' => ['nullable', 'string'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.modifiers' => ['nullable', 'array'],
            'lines.*.modifiers.*.product_modifier_option_id' => ['nullable', 'string'],
            'lines.*.modifiers.*.option_name' => ['nullable', 'string'],
            'lines.*.modifiers.*.price_adjustment' => ['nullable', 'numeric'],
        ]);

        $data['lines'] = collect($data['lines'])->map(function (array $line) {
            $variantId = $line['product_variant_id'] ?? null;
            if ($variantId === '' || $variantId === 'null') {
                $variantId = null;
            }

            return [
                ...$line,
                'product_variant_id' => $variantId,
                'modifiers' => $line['modifiers'] ?? [],
            ];
        })->values()->all();

        try {
            $data['customer_id'] = $customer->id;
            $data['guest_name'] = $data['guest_name'] ?: $customer->displayName();
            $data['guest_email'] = $data['guest_email'] ?: $customer->email;
            $data['guest_phone'] = $data['guest_phone'] ?: ($customer->phone ?: $customer->mobile);

            $order = $this->orders->placeOrder($setting, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['store' => 'We could not place your order right now. Please try again.']);
        }

        return redirect()
            ->route('storefront.orders.show', [$slug, $order->uuid])
            ->with('success', "Order {$order->order_number} placed successfully. The store has been notified.");
    }
}
