<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MultiJsonPrinter implements ResultsPrinter
{
    public function printResults(LintResultCollection $results, SymfonyStyle $io): void
    {
        $includeDetails = $io->isVerbose();
        foreach ($results as $result) {
            $io->writeln(\json_encode($result->asArray($includeDetails), \JSON_THROW_ON_ERROR));
        }
    }
}
