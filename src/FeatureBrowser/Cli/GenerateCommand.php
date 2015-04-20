<?php

namespace FeatureBrowser\FeatureBrowser\Cli;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('generate')
             ->setDescription('Generates Feature Browser Documentation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
