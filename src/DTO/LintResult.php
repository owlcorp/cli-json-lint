<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\DTO;

use OwlCorp\CliJsonLint\Command\LintJsonCommand;
use Seld\JsonLint\ParsingException;

/**
 * @phpstan-type ErrorDetails array{text?: string, token?: string|int, line?: int, loc?: array{first_line: int, first_column: int, last_line: int, last_column: int}, expected?: string[]}
 * @phpstan-type ErrorResult array{file: string|null, valid: bool, source?: string, error?: ErrorDetails}
 */
final class LintResult
{
    public function __construct(
        public readonly string $sourcePath,
        public readonly string $filePath,
        public readonly ?ParsingException $error,
    ) {
    }

    public function isFromStdIn(): bool
    {
        return $this->filePath === LintJsonCommand::STDIN_PSEUDOFILE;
    }

    /**
     * @param bool $includeDetails
     *
     * @return ErrorResult
     */
    public function asArray(bool $includeDetails = false): array
    {
        $result = ['file' => $this->isFromStdIn() ? null : $this->filePath, 'valid' => $this->error === null];
        if ($includeDetails && $this->error !== null) {
            $result['source'] = $this->sourcePath;
            $result['error'] = $this->error->getDetails();
        }

        return $result;
    }
}
