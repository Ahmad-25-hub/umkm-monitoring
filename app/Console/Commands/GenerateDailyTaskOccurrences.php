<?php

namespace App\Console\Commands;

use App\Actions\GenerateTaskOccurrencesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:generate-daily-occurrences {date? : Tanggal occurrence dalam format YYYY-MM-DD}')]
#[Description('Generate occurrence untuk seluruh tugas harian aktif')]
class GenerateDailyTaskOccurrences extends Command
{
    public function handle(GenerateTaskOccurrencesAction $generateOccurrences): int
    {
        $date = $this->argument('date');
        $createdCount = $generateOccurrences->execute(is_string($date) ? $date : null);

        $this->info("{$createdCount} occurrence tugas dibuat.");

        return self::SUCCESS;
    }
}
