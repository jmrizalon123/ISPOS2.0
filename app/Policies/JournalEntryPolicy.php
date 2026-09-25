<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;
use App\Policies\Concerns\ScopesCompanyCatalog;

class JournalEntryPolicy
{
    use ScopesCompanyCatalog;

    public function viewAny(User $user): bool
    {
        return $user->can('accounting.view');
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('accounting.view') && $this->withinCompanyScope($user, $journalEntry->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('accounting.post');
    }

    public function update(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('accounting.post')
            && $journalEntry->isDraft()
            && ! $journalEntry->source_type
            && $this->withinCompanyScope($user, $journalEntry->company_id);
    }

    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('accounting.post')
            && $journalEntry->isDraft()
            && ! $journalEntry->source_type
            && $this->withinCompanyScope($user, $journalEntry->company_id);
    }

    public function post(User $user, JournalEntry $journalEntry): bool
    {
        return $user->can('accounting.post')
            && $journalEntry->isDraft()
            && $this->withinCompanyScope($user, $journalEntry->company_id);
    }
}
