<?php

namespace App\Domains\Kds\Services;

use App\Models\KitchenTicket;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class KitchenTicketService
{
    public function createFromSale(Sale $sale): ?KitchenTicket
    {
        $sale->loadMissing(['lines.product']);

        $menuLineCount = $this->menuItemQty($sale->lines);
        if ($menuLineCount <= 0) {
            return null;
        }

        if (KitchenTicket::query()->where('sale_id', $sale->id)->exists()) {
            return KitchenTicket::query()->where('sale_id', $sale->id)->first();
        }

        return KitchenTicket::create([
            'company_id' => $sale->company_id,
            'store_id' => $sale->store_id,
            'sale_id' => $sale->id,
            'ticket_number' => 'K-'.$sale->sale_number,
            'status' => 'pending',
            'item_count' => $menuLineCount,
            'queued_at' => $sale->completed_at ?? now(),
        ]);
    }

    public function cancelForSale(Sale $sale): void
    {
        KitchenTicket::query()
            ->where('sale_id', $sale->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    public function advanceStatus(User $user, KitchenTicket $ticket, string $status): KitchenTicket
    {
        $allowed = match ($ticket->status) {
            'pending' => ['preparing'],
            'preparing' => ['ready'],
            'ready' => ['completed'],
            default => [],
        };

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move ticket from {$ticket->status} to {$status}.",
            ]);
        }

        $updates = ['status' => $status];

        if ($status === 'preparing') {
            $updates['started_at'] = now();
            $updates['started_by'] = $user->id;
        } elseif ($status === 'ready') {
            $updates['ready_at'] = now();
        } elseif ($status === 'completed') {
            $updates['completed_at'] = now();
            $updates['completed_by'] = $user->id;
        }

        $ticket->update($updates);

        return $ticket->fresh(['sale.register', 'sale.user', 'sale.lines.product', 'sale.lines.modifiers', 'sale.lines.components']);
    }

    /** @param  Collection<int, \App\Models\SaleLine>  $lines */
    protected function menuItemQty(Collection $lines): int
    {
        return (int) $lines
            ->filter(fn ($line) => $line->product?->product_type === 'menu_item')
            ->sum(fn ($line) => (int) $line->qty);
    }
}
