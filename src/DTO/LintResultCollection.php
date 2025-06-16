<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\DTO;

use Traversable;

/**
 * @phpstan-implements \IteratorAggregate<int, LintResult>
 */
final class LintResultCollection implements \Countable, \IteratorAggregate
{
    /** @var list<LintResult> */
    private array $results = [];

    private int $errors = 0;

    public function add(LintResult $result): void
    {
        $this->results[] = $result;
        if ($result->error !== null) {
            ++$this->errors;
        }
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->results);
    }

    public function count(): int
    {
        return count($this->results);
    }

    public function countErrors(): int
    {
        return $this->errors;
    }
}
