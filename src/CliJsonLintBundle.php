<?php
declare(strict_types=1);

namespace OwlCorp\CliJsonLint;

use OwlCorp\CliJsonLint\Command\LintJsonCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class CliJsonLintBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
                  ->set('console.command.json_lint', LintJsonCommand::class)
                  ->tag('console.command');
    }
}
