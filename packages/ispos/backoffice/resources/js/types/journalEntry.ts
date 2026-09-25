import type { AuditableUser } from '@/types';
import type { ChartOfAccountRecord } from '@/types/chartOfAccount';

export interface JournalEntryLineRecord {
    id?: string;
    chart_of_account_id: string;
    line_number?: number;
    description: string | null;
    debit: string | number;
    credit: string | number;
    chart_of_account?: Pick<ChartOfAccountRecord, 'id' | 'account_code' | 'account_name' | 'account_type'>;
}

export interface JournalEntryRecord {
    id: string;
    company_id: string;
    entry_number: string;
    entry_date: string;
    description: string | null;
    status: string;
    source_type: string | null;
    source_id: string | null;
    posted_at: string | null;
    created_at: string;
    updated_at: string;
    company?: { id: string; name: string };
    lines?: JournalEntryLineRecord[];
    posted_by_user?: AuditableUser | null;
}

export interface JournalEntryLineFormData {
    chart_of_account_id: string;
    description: string;
    debit: string;
    credit: string;
}

export interface JournalEntryFormData {
    company_id: string;
    entry_date: string;
    description: string;
    lines: JournalEntryLineFormData[];
}

export function defaultJournalEntryLine(accountId = ''): JournalEntryLineFormData {
    return {
        chart_of_account_id: accountId,
        description: '',
        debit: '',
        credit: '',
    };
}

export function defaultJournalEntryForm(entry?: JournalEntryRecord | null, companyId = '', defaultAccountId = ''): JournalEntryFormData {
    return {
        company_id: entry?.company_id ?? companyId,
        entry_date: entry?.entry_date ?? new Date().toISOString().slice(0, 10),
        description: entry?.description ?? '',
        lines: entry?.lines?.length
            ? entry.lines.map((line) => ({
                  chart_of_account_id: line.chart_of_account_id,
                  description: line.description ?? '',
                  debit: String(line.debit ?? ''),
                  credit: String(line.credit ?? ''),
              }))
            : [defaultJournalEntryLine(defaultAccountId), defaultJournalEntryLine(defaultAccountId)],
    };
}
