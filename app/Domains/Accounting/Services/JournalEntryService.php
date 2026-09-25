<?php

namespace App\Domains\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function __construct(protected AuditLogger $auditLogger)
    {
    }

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = JournalEntry::query()->with(['lines.chartOfAccount:id,account_code,account_name']);

        if ($user->hasGlobalOrganizationAccess()) {
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('entry_date')->orderByDesc('entry_number')->paginate($perPage)->withQueryString();
    }

    public function createDraft(User $user, array $header, array $lines): JournalEntry
    {
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($user, $header, $lines) {
            $entry = JournalEntry::create([
                'company_id' => $header['company_id'],
                'entry_number' => $this->nextEntryNumber($header['company_id']),
                'entry_date' => $header['entry_date'],
                'description' => $header['description'] ?? null,
                'status' => 'draft',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncLines($entry, $lines);
            $this->auditLogger->log('create', 'accounting', JournalEntry::class, $entry->id, null, [
                'entry_number' => $entry->entry_number,
                'status' => 'draft',
            ], $user);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    public function updateDraft(JournalEntry $entry, User $user, array $header, array $lines): JournalEntry
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft entries can be edited.']);
        }

        $this->assertBalanced($lines);

        return DB::transaction(function () use ($entry, $user, $header, $lines) {
            $entry->update([
                'entry_date' => $header['entry_date'],
                'description' => $header['description'] ?? null,
                'updated_by' => $user->id,
            ]);

            $entry->lines()->delete();
            $this->syncLines($entry, $lines);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    public function post(JournalEntry $entry, User $user): JournalEntry
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft entries can be posted.']);
        }

        $entry->load('lines');
        if ($entry->lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Journal entry must have at least one line.']);
        }

        $this->assertBalanced($entry->lines->map(fn ($line) => [
            'debit' => $line->debit,
            'credit' => $line->credit,
        ])->all());

        return DB::transaction(function () use ($entry, $user) {
            $entry->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->auditLogger->log('post', 'accounting', JournalEntry::class, $entry->id, ['status' => 'draft'], ['status' => 'posted'], $user);

            return $entry->fresh(['lines.chartOfAccount', 'postedByUser:id,name']);
        });
    }

    public function deleteDraft(JournalEntry $entry, User $user): void
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft entries can be deleted.']);
        }

        if ($entry->source_type) {
            throw ValidationException::withMessages(['entry' => 'System-generated entries cannot be deleted.']);
        }

        $this->auditLogger->log('delete', 'accounting', JournalEntry::class, $entry->id, [
            'entry_number' => $entry->entry_number,
        ], null, $user);

        $entry->delete();
    }

    /** @param  array<int, array{chart_of_account_id: string, description?: string|null, debit?: float|string, credit?: float|string}>  $lines */
    protected function syncLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $this->assertAccountBelongsToCompany($entry->company_id, $line['chart_of_account_id']);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $line['chart_of_account_id'],
                'line_number' => $index + 1,
                'description' => $line['description'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
            ]);
        }
    }

    /** @param  array<int, array{debit?: float|string, credit?: float|string}>  $lines */
    protected function assertBalanced(array $lines): void
    {
        $debits = '0';
        $credits = '0';

        foreach ($lines as $line) {
            $debits = bcadd($debits, (string) ($line['debit'] ?? 0), 4);
            $credits = bcadd($credits, (string) ($line['credit'] ?? 0), 4);
        }

        if (bccomp($debits, $credits, 4) !== 0) {
            throw ValidationException::withMessages(['lines' => 'Journal entry lines must balance (debits must equal credits).']);
        }

        if (bccomp($debits, '0', 4) === 0) {
            throw ValidationException::withMessages(['lines' => 'Journal entry must have a non-zero amount.']);
        }
    }

    protected function assertAccountBelongsToCompany(string $companyId, string $accountId): void
    {
        $exists = ChartOfAccount::query()
            ->where('company_id', $companyId)
            ->where('id', $accountId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['lines' => 'One or more accounts do not belong to this company.']);
        }
    }

    protected function nextEntryNumber(string $companyId): string
    {
        $count = JournalEntry::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        return sprintf('JE-%s-%04d', now()->format('Ymd'), $count + 1);
    }
}
