<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint\Command;

use OwlCorp\CliJsonLint\DTO\LintResult;
use OwlCorp\CliJsonLint\DTO\LintResultCollection;
use OwlCorp\CliJsonLint\Exception\IOError;
use OwlCorp\CliJsonLint\Exception\RuntimeException;
use OwlCorp\CliJsonLint\Exception\ValueError;
use OwlCorp\CliJsonLint\Formatter\JsonPrinter;
use OwlCorp\CliJsonLint\Formatter\ListPrinter;
use OwlCorp\CliJsonLint\Formatter\MultiJsonPrinter;
use OwlCorp\CliJsonLint\Formatter\TextResultsPrinter;
use Seld\JsonLint\JsonParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'lint:json', description: 'Lint JSON file(s) and report errors')]
final class LintJsonCommand extends Command
{
    public const STDIN_PSEUDOFILE = 'php://stdin';
    private const RESULT_PRINTERS = [
        'text' => TextResultsPrinter::class,
        'json' => JsonPrinter::class,
        'jsons' => MultiJsonPrinter::class,
        'list' => ListPrinter::class,
    ];

    protected function configure(): void
    {
        $this->addOption(
            'format',
            'o',
            InputOption::VALUE_REQUIRED,
            \sprintf('Print format (one of "%s")', \implode('", "', \array_keys(self::RESULT_PRINTERS))),
            'text'
        )
            //--only-errors & --no-errors
             ->addOption(
                 'exclude',
                 null,
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'Path(s) to exclude'
             )
             ->addOption(
                 'max-depth',
                 'd',
                 InputOption::VALUE_REQUIRED,
                 'How deeply to scan directories. 0 will scan only paths specified/matching pattern.',
                 100
             )
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Abort on first error')
            ->addOption('realpath', null, InputOption::VALUE_NONE, 'Return path as full filesystem path')
            ->addOption(
                'extensions',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'List of file extensions to include',
                ['json']
            )
            ->addArgument(
                'source',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Path(s) to file, directory, or glob() pattern(s). Use "-" for STDIN.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        $exclude = $this->normalizePaths($input->getOption('exclude'));
        $maxDepth = (int)$input->getOption('max-depth');
        $extensions = $input->getOption('extensions');
        $sources = $this->normalizePaths($input->getArgument('source'));

        if (!isset(self::RESULT_PRINTERS[$format])) {
            throw new ValueError(
                \sprintf(
                    'Invalid --format of "%s" specified. Valid values: "%s"',
                    $format,
                    \implode('", "', \array_keys(self::RESULT_PRINTERS))
                )
            );
        }

        $results = $this->processFiles(
            $this->getPathsMap($sources, $maxDepth, $exclude, $extensions),
            (bool)$input->getOption('stop-on-error'),
            (bool)$input->getOption('realpath')
        );
        (new (self::RESULT_PRINTERS[$format])())->printResults($results, new SymfonyStyle($input, $output));

        return $results->countErrors() === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<string> $sources
     * @param array<string> $excludes
     * @param array<string> $extensions
     *
     * @return iterable<string, \SplFileInfo>
     */
    private function getPathsMap(array $sources, int $recurseLimit, array $excludes, array $extensions): iterable
    {
        if (\in_array('-', $sources, true)) {
            if (\count($sources) > 1) {
                throw new RuntimeException(
                    'Source argument should contain a filesystem path OR "-" for STDIN. ' .
                    'You cannot use both at the same time.'
                );
            }

            yield '-' => new \SplFileInfo(self::STDIN_PSEUDOFILE);
            return;
        }

        $seen = [];
        foreach ($sources as $source) {
            //glob() will return a path to file/directory if just a path was specified as pattern
            $glob = \glob($source, $this->getGlobFlags($source));
            if ($glob === false) {
                throw new IOError(\sprintf('Glob failed- unable to locate "%s"', $source));
            }

            foreach ($glob as $node) {
                yield from $this->scanDirectory(
                    $source,
                    new \SplFileInfo($node),
                    $recurseLimit + 1,
                    $excludes,
                    $extensions,
                    $seen
                );
            }
        }
    }

    private function getGlobFlags(string $source): int
    {
        if (!\defined('GLOB_BRACE') && \str_contains($source, '{')) {
            throw new RuntimeException(
                \sprintf(
                    'The pattern "%s" uses {...} braces matching that is not supported on your operating system. ' .
                    'See https://www.php.net/manual/en/filesystem.constants.php#constant.glob-brace for details.',
                    $source
                )
            );
        }

        return \GLOB_BRACE|\GLOB_BRACE;
    }

    /**
     * @param array<string>        $excludes
     * @param array<string>        $extensions
     * @param array<string, true> &$seen
     *
     * @return iterable<string, \SplFileInfo>
     */
    private function scanDirectory(
        string $source,
        \SplFileInfo $dirPath,
        int $recurseLimit,
        array $excludes,
        array $extensions,
        array &$seen = []
    ): iterable {
        $rpath = $dirPath->getRealPath();
        if ($rpath === false || isset($seen[$rpath])) { //break loops & broken symlinks
            return;
        }
        $seen[$rpath] = true;

        if (\in_array($dirPath->getPathname(), $excludes, true)) {
            return;
        }

        if ($dirPath->isFile()) {
            if (!\in_array($dirPath->getExtension(), $extensions, true)) {
                return;
            }

            yield $source => $dirPath;
        }

        if (!$dirPath->isDir() || $recurseLimit <= 0) {
            return;
        }

        /** @var iterable<\SplFileInfo> $iter */
        $iter = new \FilesystemIterator(
            $dirPath->getPathname(),
            \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS |
            \FilesystemIterator::FOLLOW_SYMLINKS
        );
        foreach ($iter as $innerDir) {
            yield from $this->scanDirectory($source, $innerDir, $recurseLimit - 1, $excludes, $extensions, $seen);
        }
    }

    /**
     * @param array<mixed, string> $paths
     *
     * @return array<mixed, string>
     */
    private function normalizePaths(array $paths): array
    {
        foreach ($paths as $key => $path) {
            $paths[$key] = \rtrim($path, \PATH_SEPARATOR); //backslash is valid in path on e.g. macOS but not Windows
        }

        return \array_unique($paths);
    }

    /**
     * @param iterable<string, \SplFileInfo> $map
     */
    private function processFiles(iterable $map, bool $stopOnError, bool $realpath): LintResultCollection
    {
        $linter = new JsonParser();
        $results = new LintResultCollection();
        foreach ($map as $source => $file) {
            $filePath = $file->getPathname();
            $fileCnt = \file_get_contents($filePath);
            if ($fileCnt === false) {
                throw new IOError('Unable to read file: ' . $file);
            }

            $lintResult = $linter->lint($fileCnt, JsonParser::DETECT_KEY_CONFLICTS);
            $path = $realpath && $file !== self::STDIN_PSEUDOFILE ? $file->getRealPath() : $filePath;
            $result = new LintResult($source, $path, $lintResult);
            $results->add($result);
            if ($result->error !== null && $stopOnError) {
                break;
            }
        }

        return $results;
    }
}
