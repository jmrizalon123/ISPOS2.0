<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductRating;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontRatingController extends Controller
{
    public function __construct(protected OnlineStoreSettingService $settings) {}

    public function store(Request $request, string $slug, string $product): JsonResponse|RedirectResponse
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        /** @var Customer|null $customer */
        $customer = auth('customer')->user();
        if (! $customer) {
            return redirect()->route('storefront.login', $slug)
                ->with('error', 'Please sign in to rate products.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $productModel = Product::query()
            ->where('company_id', $setting->store->company_id)
            ->whereKey($product)
            ->firstOrFail();

        ProductRating::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'store_id' => $setting->store_id,
                'product_id' => $productModel->id,
            ],
            [
                'company_id' => $setting->store->company_id,
                'rating' => $data['rating'],
                'review' => $data['review'] ?? null,
            ],
        );

        if ($request->header('X-Inertia') || ! $request->expectsJson()) {
            return back()->with('success', 'Thanks for your rating!');
        }

        $stats = $this->productStats($productModel->id, $setting->store_id);

        return response()->json([
            'product_id' => $productModel->id,
            'my_rating' => (int) $data['rating'],
            ...$stats,
        ]);
    }

    /** @return array{avg_rating: float, rating_count: int} */
    protected function productStats(string $productId, string $storeId): array
    {
        $query = ProductRating::query()
            ->where('product_id', $productId)
            ->where('store_id', $storeId);

        return [
            'avg_rating' => round((float) $query->avg('rating'), 1),
            'rating_count' => (int) $query->count(),
        ];
    }
}
