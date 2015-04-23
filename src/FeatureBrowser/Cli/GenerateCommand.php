<?php

namespace FeatureBrowser\FeatureBrowser\Cli;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('generate')
             ->setDescription('Generates Feature Browser Documentation')
             ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Where do you want the generated documentation to be stored?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputDir = $input->getOption('output-dir');
        if(!is_dir($outputDir))
        {
            mkdir($outputDir);
        }

    }
}
