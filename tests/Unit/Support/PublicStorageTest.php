<?php

namespace Tests\Unit\Support;

use App\Support\PublicStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageTest extends TestCase
{
    public function test_url_uses_relative_storage_path(): void
    {
        $this->assertSame('/storage/products/company/product/file.jpg', PublicStorage::url('products/company/product/file.jpg'));
    }

    public function test_url_preserves_absolute_http_paths(): void
    {
        $this->assertSame('https://cdn.example.com/image.jpg', PublicStorage::url('https://cdn.example.com/image.jpg'));
    }

    public function test_url_preserves_root_relative_paths(): void
    {
        $this->assertSame('/storage/existing.jpg', PublicStorage::url('/storage/existing.jpg'));
    }

    public function test_publish_skips_when_public_storage_points_to_same_directory(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('product.jpg');
        $path = PublicStorage::storeUploadedFile($file, 'products/test-company/test-product', 'sample.jpg');

        Storage::disk('public')->assertExists($path);
        $this->assertSame('/storage/'.$path, PublicStorage::url($path));
    }
}
