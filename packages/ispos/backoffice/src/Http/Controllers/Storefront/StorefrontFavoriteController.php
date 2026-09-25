<?php

namespace Ispos\Backoffice\Http\Controllers\Storefront;

use App\Domains\OnlineStore\Services\OnlineStoreSettingService;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductFavorite;
use Ispos\Backoffice\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontFavoriteController extends Controller
{
    public function __construct(protected OnlineStoreSettingService $settings) {}

    public function toggle(Request $request, string $slug, string $product): JsonResponse|RedirectResponse
    {
        $setting = $this->settings->findPublishedBySlug($slug);
        if (! $setting) {
            throw new NotFoundHttpException('This online store is not available.');
        }

        /** @var Customer|null $customer */
        $customer = auth('customer')->user();
        if (! $customer) {
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return response()->json(['message' => 'Login required'], 401);
            }

            return redirect()->route('storefront.login', $slug);
        }

        $productModel = Product::query()
            ->where('company_id', $setting->store->company_id)
            ->whereKey($product)
            ->firstOrFail();

        $existing = ProductFavorite::query()
            ->where('customer_id', $customer->id)
            ->where('store_id', $setting->store_id)
            ->where('product_id', $productModel->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            ProductFavorite::create([
                'company_id' => $setting->store->company_id,
                'store_id' => $setting->store_id,
                'customer_id' => $customer->id,
                'product_id' => $productModel->id,
            ]);
            $favorited = true;
        }

        if ($request->header('X-Inertia')) {
            return back();
        }

        return response()->json(['favorited' => $favorited, 'product_id' => $productModel->id]);
    }
}
