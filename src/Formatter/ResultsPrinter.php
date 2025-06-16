<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use Symfony\Component\Console\Style\SymfonyStyle;

interface ResultsPrinter
{
    public function printResults(LintResultCollection $results, SymfonyStyle $io): void;
}
