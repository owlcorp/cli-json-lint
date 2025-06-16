<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ListPrinter implements ResultsPrinter
{
    public function printResults(LintResultCollection $results, SymfonyStyle $io): void
    {
        foreach ($results as $result) {
            if ($result->error !== null) {
                $io->writeln($result->filePath);
            }
        }
    }
}
