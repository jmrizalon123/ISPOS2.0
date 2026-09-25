<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntry> */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'entry_number' => 'JE-'.now()->format('Ymd').'-0001',
            'entry_date' => now()->toDateString(),
            'description' => fake()->sentence(),
            'status' => 'draft',
        ];
    }
}
