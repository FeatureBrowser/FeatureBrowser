<?php

namespace FeatureBrowser\FeatureBrowser\Cli;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Twig_Loader_Filesystem;
use Twig_Environment;


final class GenerateCommand extends BaseCommand
{
    protected $configFile = 'featurebrowser.yml.dist';
    protected $projectName;
    protected $outputDirectory;
    protected $featuresDirectory;

    protected function configure()
    {
        $this->setName('generate')
             ->setDescription('Generates Feature Browser Documentation')
             ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Where do you want the generated documentation to be stored?');
    }

    protected function loadConfig(InputInterface $input)
    {
        //Read the config file to get default parameters
        $configs                 = Yaml::parse($this->configFile);
        $this->projectName       = $configs['featurebrowser']['project-name'];
        $this->featuresDirectory = $configs['featurebrowser']['features-directory'];

        $outputDir = $input->getOption('output-dir');
        if(null === $outputDir)
        {
            $outputDir = $configs['featurebrowser']['output-directory'];
        }

        if(!is_dir($outputDir))
        {
            mkdir($outputDir);
        }
        $outputDir .= (substr($outputDir, -1) == '/' ? '' : '/');
        $this->outputDirectory = $outputDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig($input);

        $keywords = new ArrayKeywords(
            [
                'en' => [
                    'name'             => 'English',
                    'native'           => 'English',
                    'feature'          => 'Feature|Business Need|Ability',
                    'background'       => 'Background',
                    'scenario'         => 'Scenario',
                    'scenario_outline' => 'Scenario Outline|Scenario Template',
                    'examples'         => 'Examples|Scenarios',
                    'given'            => 'Given',
                    'when'             => 'When',
                    'then'             => 'Then',
                    'and'              => 'And',
                    'but'              => 'But',
                ]
            ]
        );

        $lexer  = new Lexer($keywords);
        $parser = new Parser($lexer);

        $features = [];
        $tags     = [];

        $finder = new Finder();
        $finder->files()->in($this->featuresDirectory)->name('*.feature');
        /** @var \Symfony\Component\Finder\SplFileInfo $featureFile */
        foreach($finder as $featureFile)
        {
            $featureFromFile = $parser->parse($featureFile->getContents(), $featureFile->getRealPath());
            if($featureFromFile instanceof FeatureNode)
            {
                $pathname = $featureFile->getPathname();
                $pathname = str_replace($this->featuresDirectory . DIRECTORY_SEPARATOR, '', $pathname);
                if(DIRECTORY_SEPARATOR != '/')
                {
                    $pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname);
                }
                $pathname = str_replace('.feature', '.html', $pathname);

                $features[$pathname] = $featureFromFile;
                $tags                = array_merge($tags, $featureFromFile->getTags());
                $scenarios           = $featureFromFile->getScenarios();
                foreach($scenarios AS $scenario)
                {
                    $tags = array_merge($tags, $scenario->getTags());
                }
            }
        }

        $tags = array_count_values($tags);
        arsort($tags);

        $viewsDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
        $loader         = new Twig_Loader_Filesystem($viewsDirectory, ['cache' => '/cache',]);
        $twig           = new Twig_Environment($loader);

        $templateVariables = [
            'projectName' => $this->projectName,
            'features'    => $features,
            'tags'        => $tags
        ];
        $rendered          = $twig->render('base.html.twig', $templateVariables);
        $filePointer       = fopen($this->outputDirectory . 'index.html', 'w');
        fwrite($filePointer, $rendered);
    }
}
