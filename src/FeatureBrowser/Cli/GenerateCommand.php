<?php

namespace FeatureBrowser\FeatureBrowser\Cli;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig_Loader_Filesystem;
use Twig_Environment;


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
        $outputDir .= (substr($outputDir, -1) == '/' ? '' : '/');

        $viewsDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
        $loader         = new Twig_Loader_Filesystem($viewsDirectory, ['cache' => '/cache',]);
        $twig           = new Twig_Environment($loader);

        $rendered = $twig->render('base.html.twig');
        $filePointer       = fopen($outputDir . 'index.html', 'w');
        fwrite($filePointer, $rendered);
    }
}
