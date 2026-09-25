<?php

namespace App\Domains\Catalog\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\PublicStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductImageService
{
    public const MAX_UPLOAD_KILOBYTES = 1024;

    public const MAX_IMAGES_PER_PRODUCT = 10;

    /**
     * @param  list<UploadedFile>  $uploads
     * @param  list<array<string, mixed>>  $rows
     */
    public function sync(Product $product, array $rows, array $uploads, ?User $actor = null): void
    {
        $rows = array_values($rows);
        $uploads = array_values($uploads);

        if (count($rows) > self::MAX_IMAGES_PER_PRODUCT) {
            throw new \InvalidArgumentException('Too many product images.');
        }

        $keepIds = [];
        $defaultId = null;

        foreach ($rows as $index => $row) {
            $isDefault = (bool) ($row['is_default'] ?? false);
            $sortOrder = $row['sort_order'] ?? $index;

            if (! empty($row['id'])) {
                $image = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($row['id']);

                $payload = [
                    'sort_order' => $sortOrder,
                ];

                if ($actor) {
                    $payload['updated_by'] = $actor->id;
                }

                $image->update($payload);
                $keepIds[] = $image->id;

                if ($isDefault) {
                    $defaultId = $image->id;
                }

                continue;
            }

            $uploadIndex = $row['upload_index'] ?? null;
            if ($uploadIndex === null || ! isset($uploads[$uploadIndex])) {
                continue;
            }

            $file = $uploads[$uploadIndex];
            $storedPath = $this->storeUpload($product, $file);

            $payload = [
                'product_id' => $product->id,
                'path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $sortOrder,
                'is_default' => false,
            ];

            if ($actor) {
                $payload['created_by'] = $actor->id;
                $payload['updated_by'] = $actor->id;
            }

            $image = ProductImage::create($payload);
            $keepIds[] = $image->id;

            if ($isDefault) {
                $defaultId = $image->id;
            }
        }

        ProductImage::query()
            ->where('product_id', $product->id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->get()
            ->each(fn (ProductImage $image) => $this->deleteImage($image));

        if ($keepIds === []) {
            $product->update(['image' => null]);

            return;
        }

        if (! $defaultId) {
            $defaultId = $keepIds[0];
        }

        ProductImage::query()
            ->where('product_id', $product->id)
            ->update(['is_default' => false]);

        $default = ProductImage::query()
            ->where('product_id', $product->id)
            ->find($defaultId);

        if ($default) {
            $default->update(['is_default' => true]);
            $product->update(['image' => $default->path]);
        }
    }

    protected function storeUpload(Product $product, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $filename = Str::uuid()->toString().'.'.strtolower($extension);
        $directory = 'products/'.$product->company_id.'/'.$product->id;

        return PublicStorage::storeUploadedFile($file, $directory, $filename);
    }

    public function deleteImage(ProductImage $image): void
    {
        PublicStorage::delete($image->path);

        $image->delete();
    }
}
