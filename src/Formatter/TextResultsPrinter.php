<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Formatter;

use OwlCorp\CliJsonLint\DTO\LintResult;
use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TextResultsPrinter implements ResultsPrinter
{
    public function printResults(LintResultCollection $results, SymfonyStyle $io): void
    {
        $this->printIndividualResults($results, $io->isVerbose(), $io->isVeryVerbose(), $io);
        $this->printSummary($results, $io);
    }

    private function printIndividualResults(
        LintResultCollection $results,
        bool $printDetails,
        bool $printSuccess,
        SymfonyStyle $io
    ): void {
        $errors = $results->countErrors();
        $e = 0;
        foreach ($results as $result) {
            if ($result->error !== null) {
                $error = \sprintf('Error %d of %d: JSON %s is invalid', ++$e, $errors, $this->resolveFile($result));
                if ($printDetails) {
                    $error .= "\n\n" . $result->error->getMessage();
                }
                $io->block($error, null, 'fg=white;bg=red', ' ', true);
            } elseif ($printSuccess) {
                $io->comment(\sprintf('<info>JSON %s is valid</info>', $this->resolveFile($result)));
            }
        }
    }

    private function printSummary(LintResultCollection $results, SymfonyStyle $io): void
    {
        $parsed = $results->count();
        $errors = $results->countErrors();
        if ($parsed === 0) {
            $io->warning('No files were parsed.');
        } elseif ($errors === $parsed) {
            $io->error(\sprintf('All %d JSON files contained syntax errors.', $errors));
        } elseif ($errors > 0) {
            $io->error(\sprintf('%d of %d JSON files contained syntax errors.', $errors, $parsed));
        } else {
            $io->success(\sprintf('All %d JSON files are valid.', $parsed));
        }
    }

    private function resolveFile(LintResult $result): string
    {
        $path = $result->isFromStdIn() ? 'read from STDIN' : "in $result->filePath file";
        if ($path !== $result->sourcePath) {
            $path .= ' (resolved from "' . $result->sourcePath . '")';
        }

        return $path;
    }
}
