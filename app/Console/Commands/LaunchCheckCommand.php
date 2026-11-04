<?php

namespace App\Console\Commands;

use App\Services\LaunchChecklist;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('guidance:check')]
#[Description('Check that this installation is safe to use with real student data')]
class LaunchCheckCommand extends Command
{
    public function handle(LaunchChecklist $checklist): int
    {
        $results = collect($checklist->run());

        $this->table(
            ['Area', 'Check', 'Result', 'What to do'],
            $results->map(fn (array $row): array => [
                $row['area'],
                $row['check'],
                match ($row['result']) {
                    'pass' => '<fg=green>PASS</>',
                    'warn' => '<fg=yellow>WARN</>',
                    'fail' => '<fg=red>FAIL</>',
                },
                $row['detail'],
            ]),
        );

        $failures = $results->where('result', 'fail')->count();
        $warnings = $results->where('result', 'warn')->count();

        if ($failures > 0) {
            $this->components->error("{$failures} check(s) failed. Fix them before entering real student data.");

            return self::FAILURE;
        }

        $warnings > 0
            ? $this->components->warn("Ready, with {$warnings} warning(s) to review.")
            : $this->components->info('All checks passed.');

        return self::SUCCESS;
    }
}
