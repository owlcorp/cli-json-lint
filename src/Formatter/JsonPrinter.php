<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use Symfony\Component\Console\Style\SymfonyStyle;

final class JsonPrinter implements ResultsPrinter
{
    public function printResults(LintResultCollection $results, SymfonyStyle $io): void
    {
        $includeDetails = $io->isVerbose();
        $json = [];
        foreach ($results as $result) {
            $json[] = $result->asArray($includeDetails);
        }

        $io->writeln(\json_encode($json, \JSON_PRETTY_PRINT|\JSON_THROW_ON_ERROR));
    }
}
