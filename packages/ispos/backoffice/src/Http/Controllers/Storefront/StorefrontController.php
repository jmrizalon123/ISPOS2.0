<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Domains\Sales\Services\PosCatalogService;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ProductFavorite;
use App\Models\ProductRating;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontController extends Controller
{
    public function __construct(
        protected OnlineStoreSettingService $settings,
        protected PosCatalogService $catalog,
    ) {}

    public function show(Request $request, string $slug): Response
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        $store = $setting->store;
        $products = $this->catalog->listForStore(
            $store,
            $request->string('q')->toString() ?: null,
            null,
        )->map(fn ($product) => $this->catalog->serializeProduct($product))->values()->all();

        $productIds = collect($products)->pluck('id')->filter()->values()->all();
        $ratingStats = $this->ratingStatsForProducts($productIds, $store->id);
        $products = collect($products)->map(function (array $product) use ($ratingStats) {
            $stats = $ratingStats[$product['id']] ?? ['avg_rating' => 0, 'rating_count' => 0];
            $product['avg_rating'] = $stats['avg_rating'];
            $product['rating_count'] = $stats['rating_count'];

            return $product;
        })->values()->all();

        $categories = Category::query()
            ->where('company_id', $store->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'category_code'])
            ->all();

        $layout = match ($store->store_category) {
            'restaurant', 'cafe' => 'menu',
            default => 'grid',
        };

        /** @var Customer|null $customer */
        $customer = auth('customer')->user();
        $favoriteIds = [];
        $myRatings = [];
        if ($customer) {
            $favoriteIds = ProductFavorite::query()
                ->where('customer_id', $customer->id)
                ->where('store_id', $store->id)
                ->pluck('product_id')
                ->all();
            $myRatings = ProductRating::query()
                ->where('customer_id', $customer->id)
                ->where('store_id', $store->id)
                ->get(['product_id', 'rating', 'review'])
                ->mapWithKeys(fn ($row) => [
                    $row->product_id => [
                        'rating' => (int) $row->rating,
                        'review' => $row->review,
                    ],
                ])
                ->all();
        }

        $prices = collect($products)->map(fn ($p) => (float) ($p['pos_unit_price'] ?? 0))->filter(fn ($p) => $p > 0);
        $priceMin = $prices->isNotEmpty() ? (float) $prices->min() : 0;
        $priceMax = $prices->isNotEmpty() ? (float) $prices->max() : 0;

        return Inertia::render('Storefront/Home', [
            'setting' => $this->serializeSetting($setting, $store->logo_url),
            'store' => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'store_code' => $store->store_code,
                'store_category' => $store->store_category,
                'address_line_1' => $store->address_line_1,
                'city' => $store->city,
                'phone' => $store->phone ?: $store->mobile,
                'currency' => $store->currency ?: 'PHP',
                'logo_url' => $store->logo_url,
            ],
            'categories' => $categories,
            'products' => $products,
            'layout' => $layout,
            'acceptingOrders' => $setting->isAcceptingOrdersNow(),
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->displayName(),
                'email' => $customer->email,
                'phone' => $customer->phone ?: $customer->mobile,
            ] : null,
            'favoriteIds' => $favoriteIds,
            'myRatings' => $myRatings,
            'priceBounds' => [
                'min' => $priceMin,
                'max' => $priceMax,
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->string('category')->toString(),
                'price_min' => $request->string('price_min')->toString(),
                'price_max' => $request->string('price_max')->toString(),
            ],
        ]);
    }

    /**
     * @param  list<string>  $productIds
     * @return array<string, array{avg_rating: float, rating_count: int}>
     */
    protected function ratingStatsForProducts(array $productIds, string $storeId): array
    {
        if ($productIds === []) {
            return [];
        }

        return ProductRating::query()
            ->select('product_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as rating_count'))
            ->where('store_id', $storeId)
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->product_id => [
                    'avg_rating' => round((float) $row->avg_rating, 1),
                    'rating_count' => (int) $row->rating_count,
                ],
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    protected function serializeSetting($setting, ?string $storeLogoUrl = null): array
    {
        return [
            'id' => $setting->id,
            'slug' => $setting->slug,
            'storefront_name' => $setting->storefront_name,
            'tagline' => $setting->tagline,
            'description' => $setting->description,
            'logo_url' => $storeLogoUrl ?: $setting->logo_url,
            'hero_image_url' => $setting->hero_image_url,
            'primary_color' => $setting->primary_color ?: '#0f766e',
            'accent_color' => $setting->accent_color ?: '#f59e0b',
            'accept_pickup' => $setting->accept_pickup,
            'accept_delivery' => $setting->accept_delivery,
            'accept_dine_in' => $setting->accept_dine_in,
            'min_order_amount' => $setting->min_order_amount,
            'delivery_fee' => $setting->delivery_fee,
            'free_delivery_threshold' => $setting->free_delivery_threshold,
            'preparation_minutes' => $setting->preparation_minutes,
            'payment_methods' => $setting->payment_methods ?: ['cod', 'pay_at_store'],
            'announcement' => $setting->announcement,
            'support_phone' => $setting->support_phone,
            'support_email' => $setting->support_email,
            'orders_open_at' => $setting->orders_open_at,
            'orders_close_at' => $setting->orders_close_at,
            'seo_title' => $setting->seo_title,
            'seo_description' => $setting->seo_description,
            'homepage' => \App\Domains\OnlineStore\Support\StorefrontHomepageDefaults::merge($setting->homepage),
            'public_url' => $setting->public_url,
        ];
    }
}
