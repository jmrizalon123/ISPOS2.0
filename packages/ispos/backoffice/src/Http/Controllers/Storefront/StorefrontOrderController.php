<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Models\Customer;
use App\Models\OnlineOrder;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontOrderController extends Controller
{
    public function __construct(protected OnlineStoreSettingService $settings) {}

    public function index(Request $request, string $slug): Response
    {
        $setting = $this->publishedSetting($slug);
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $orders = OnlineOrder::query()
            ->with(['lines:id,online_order_id,name,qty,line_total'])
            ->where('store_id', $setting->store_id)
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (OnlineOrder $order) => $this->serializeOrderSummary($order))
            ->values()
            ->all();

        return Inertia::render('Storefront/Orders/Index', [
            'setting' => $this->settingProps($setting),
            'store' => [
                'store_name' => $setting->store->store_name,
                'currency' => $setting->store->currency ?: 'PHP',
                'logo_url' => $setting->store->logo_url,
            ],
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->displayName(),
            ],
            'orders' => $orders,
        ]);
    }

    public function show(string $slug, string $uuid): Response
    {
        $setting = $this->publishedSetting($slug);

        $order = OnlineOrder::query()
            ->with(['lines.modifiers', 'store:id,store_name,store_code'])
            ->where('uuid', $uuid)
            ->where('store_id', $setting->store_id)
            ->firstOrFail();

        /** @var Customer|null $customer */
        $customer = auth('customer')->user();
        $isOwner = $customer && $order->customer_id && $order->customer_id === $customer->id;

        return Inertia::render('Storefront/OrderConfirmation', [
            'setting' => $this->settingProps($setting),
            'store' => [
                'store_name' => $setting->store->store_name,
                'logo_url' => $setting->store->logo_url,
            ],
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->displayName(),
            ] : null,
            'isOwner' => (bool) $isOwner,
            'order' => $this->serializeOrderDetail($order),
        ]);
    }

    protected function publishedSetting(string $slug)
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        return $setting;
    }

    /** @return array<string, mixed> */
    protected function settingProps($setting): array
    {
        return [
            'slug' => $setting->slug,
            'storefront_name' => $setting->storefront_name,
            'primary_color' => $setting->primary_color ?: '#0f766e',
            'accent_color' => $setting->accent_color ?: '#f59e0b',
            'logo_url' => $setting->store->logo_url ?: $setting->logo_url,
            'support_phone' => $setting->support_phone,
            'support_email' => $setting->support_email,
        ];
    }

    /** @return array<string, mixed> */
    protected function serializeOrderSummary(OnlineOrder $order): array
    {
        return [
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'fulfillment_type' => $order->fulfillment_type,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'grand_total' => $order->grand_total,
            'currency' => $order->currency,
            'item_count' => $order->lines->sum(fn ($line) => (float) $line->qty),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    protected function serializeOrderDetail(OnlineOrder $order): array
    {
        return [
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'fulfillment_type' => $order->fulfillment_type,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'guest_name' => $order->guest_name,
            'guest_phone' => $order->guest_phone,
            'guest_email' => $order->guest_email,
            'subtotal' => $order->subtotal,
            'tax_total' => $order->tax_total,
            'discount_total' => $order->discount_total,
            'delivery_fee' => $order->delivery_fee,
            'grand_total' => $order->grand_total,
            'currency' => $order->currency,
            'customer_notes' => $order->customer_notes,
            'rejection_reason' => $order->rejection_reason,
            'created_at' => $order->created_at?->toIso8601String(),
            'accepted_at' => $order->accepted_at?->toIso8601String(),
            'ready_at' => $order->ready_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'lines' => $order->lines->map(fn ($line) => [
                'name' => $line->name,
                'qty' => $line->qty,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
                'modifiers' => $line->modifiers->map(fn ($m) => [
                    'modifier_group_name' => $m->modifier_group_name,
                    'option_name' => $m->option_name,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
