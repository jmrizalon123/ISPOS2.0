<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Accounting\Services\JournalEntryService;
use App\Domains\Accounting\Services\TrialBalanceReportService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreJournalEntryRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected TrialBalanceReportService $trialBalanceReportService,
    ) {
        $this->authorizeResource(JournalEntry::class, 'journal_entry');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Accounting/JournalEntries/Index', [
            'entries' => $this->journalEntryService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Accounting/JournalEntries/Form', [
            'entry' => null,
            'companies' => $this->companyOptions($request),
            'accounts' => $this->trialBalanceReportService
                ->activeAccounts($request->user(), $ctx['companyId'])
                ->values(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $entry = $this->journalEntryService->createDraft(
            $request->user(),
            [
                'company_id' => $validated['company_id'],
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'] ?? null,
            ],
            $validated['lines'],
        );

        return redirect()->route('admin.journal-entries.edit', $entry)->with('success', 'Journal entry saved as draft.');
    }

    public function edit(Request $request, JournalEntry $journalEntry): Response
    {
        return Inertia::render('Admin/Accounting/JournalEntries/Form', [
            'entry' => $journalEntry->load(['lines.chartOfAccount', 'company:id,name', 'postedByUser:id,name']),
            'companies' => $this->companyOptions($request),
            'accounts' => $this->trialBalanceReportService
                ->activeAccounts($request->user(), $journalEntry->company_id)
                ->values(),
        ]);
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): RedirectResponse
    {
        $validated = $request->validated();
        $this->journalEntryService->updateDraft(
            $journalEntry,
            $request->user(),
            [
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'] ?? null,
            ],
            $validated['lines'],
        );

        return redirect()->route('admin.journal-entries.edit', $journalEntry)->with('success', 'Journal entry updated.');
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->journalEntryService->deleteDraft($journalEntry, auth()->user());

        return redirect()->route('admin.journal-entries.index')->with('success', 'Journal entry deleted.');
    }

    public function post(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('post', $journalEntry);
        $this->journalEntryService->post($journalEntry, $request->user());

        return redirect()->route('admin.journal-entries.edit', $journalEntry)->with('success', 'Journal entry posted.');
    }
}
