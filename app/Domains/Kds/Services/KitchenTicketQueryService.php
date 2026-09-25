<?php

namespace App\Domains\Kds\Services;

use App\Models\KitchenTicket;
use App\Models\Store;
use Illuminate\Support\Collection;

class KitchenTicketQueryService
{
    /** @return Collection<int, array<string, mixed>> */
    public function activeForStore(Store $store): Collection
    {
        return KitchenTicket::query()
            ->with(['sale.register', 'sale.user', 'sale.lines.product', 'sale.lines.modifiers', 'sale.lines.components'])
            ->where('store_id', $store->id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderBy('queued_at')
            ->get()
            ->map(fn (KitchenTicket $ticket) => $this->serialize($ticket));
    }

    /** @return array<string, mixed> */
    public function serialize(KitchenTicket $ticket): array
    {
        $ticket->loadMissing(['sale.register', 'sale.user', 'sale.lines.product', 'sale.lines.modifiers', 'sale.lines.components']);

        $menuLines = $ticket->sale->lines
            ->filter(fn ($line) => $line->product?->product_type === 'menu_item')
            ->values();

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'sale_number' => $ticket->sale->sale_number,
            'status' => $ticket->status,
            'item_count' => $ticket->item_count,
            'queued_at' => $ticket->queued_at?->toIso8601String(),
            'started_at' => $ticket->started_at?->toIso8601String(),
            'ready_at' => $ticket->ready_at?->toIso8601String(),
            'register_name' => $ticket->sale->register?->register_name,
            'cashier_name' => $ticket->sale->user?->name,
            'lines' => $menuLines->map(fn ($line) => [
                'id' => $line->id,
                'name' => $line->name,
                'qty' => (string) $line->qty,
                'modifiers' => $line->modifiers->map(fn ($modifier) => [
                    'group_name' => $modifier->modifier_group_name,
                    'option_name' => $modifier->option_name,
                ])->values()->all(),
                'components' => $line->components
                    ->filter(fn ($component) => $component->included)
                    ->map(fn ($component) => [
                        'name' => $component->component_name,
                        'quantity' => (string) $component->quantity,
                    ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
