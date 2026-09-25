<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnlineStoreSetting extends Model
{
    use HasAuditUsers, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'store_id',
        'slug',
        'is_published',
        'storefront_name',
        'tagline',
        'description',
        'logo',
        'hero_image',
        'primary_color',
        'accent_color',
        'accept_pickup',
        'accept_delivery',
        'accept_dine_in',
        'min_order_amount',
        'delivery_fee',
        'free_delivery_threshold',
        'preparation_minutes',
        'payment_methods',
        'auto_accept_orders',
        'announcement',
        'support_phone',
        'support_email',
        'orders_open_at',
        'orders_close_at',
        'orders_open_days',
        'seo_title',
        'seo_description',
        'homepage',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'accept_pickup' => 'boolean',
            'accept_delivery' => 'boolean',
            'accept_dine_in' => 'boolean',
            'min_order_amount' => 'decimal:4',
            'delivery_fee' => 'decimal:4',
            'free_delivery_threshold' => 'decimal:4',
            'preparation_minutes' => 'integer',
            'payment_methods' => 'array',
            'auto_accept_orders' => 'boolean',
            'orders_open_days' => 'array',
            'homepage' => 'array',
        ];
    }

    protected $appends = ['logo_url', 'hero_image_url', 'public_url'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->hero_image ? Storage::disk('public')->url($this->hero_image) : null;
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/store/'.$this->slug);
    }

    public function isAcceptingOrdersNow(): bool
    {
        if (! $this->is_published || $this->status !== 'active') {
            return false;
        }

        $days = $this->orders_open_days;
        if (is_array($days) && count($days) > 0) {
            $today = Str::lower(now()->format('D')); // mon, tue…
            $map = [
                'mon' => 'monday', 'tue' => 'tuesday', 'wed' => 'wednesday',
                'thu' => 'thursday', 'fri' => 'friday', 'sat' => 'saturday', 'sun' => 'sunday',
            ];
            $full = $map[$today] ?? $today;
            $normalized = array_map(fn ($d) => Str::lower((string) $d), $days);
            if (! in_array($today, $normalized, true) && ! in_array($full, $normalized, true)) {
                return false;
            }
        }

        if ($this->orders_open_at && $this->orders_close_at) {
            $now = now()->format('H:i:s');
            $open = (string) $this->orders_open_at;
            $close = (string) $this->orders_close_at;
            if ($open <= $close) {
                return $now >= $open && $now <= $close;
            }

            // Overnight window
            return $now >= $open || $now <= $close;
        }

        return true;
    }

    public function resolvedDeliveryFee(float $subtotal): string
    {
        if ($this->free_delivery_threshold !== null
            && (float) $this->free_delivery_threshold > 0
            && $subtotal >= (float) $this->free_delivery_threshold) {
            return '0.0000';
        }

        return (string) ($this->delivery_fee ?? '0.0000');
    }
}
