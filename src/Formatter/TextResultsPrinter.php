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
        $printErrDetails = $io->isVerbose();
        $printNonErr = $io->isVeryVerbose();

        $errors = $results->countErrors();
        $e = 0;
        foreach ($results as $result) {
            if ($result->error !== null) {
                $error = \sprintf('Error %d of %d: JSON %s is invalid', ++$e, $errors, $this->resolveFile($result));
                if ($printErrDetails) {
                    $error .= "\n\n" . $result->error->getMessage();
                }
                $io->block($error, null, 'fg=white;bg=red', ' ', true);
            } elseif ($printNonErr) {
                $io->comment(\sprintf('<info>JSON %s is valid</info>', $this->resolveFile($result)));
            }
        }

        $parsed = $results->count();
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
