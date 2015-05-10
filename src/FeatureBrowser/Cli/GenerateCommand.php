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
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Twig_Loader_Filesystem;
use Twig_Environment;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;


final class GenerateCommand extends BaseCommand
{
    protected $configFile  = 'featurebrowser.yml.dist';
    protected $projectName;
    protected $outputDirectory;
    protected $featuresDirectory;
    protected $features    = [];
    protected $tags        = [];
    protected $directories = [];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('generate')
             ->setDescription('Generates Feature Browser Documentation')
             ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Where do you want the generated documentation to be stored?');
    }

    /**
     * @param InputInterface $input
     */
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

    /**
     * @return ArrayKeywords
     */
    protected function getKeywords()
    {
        return new ArrayKeywords(
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
    }

    /**
     * @return Parser
     */
    protected function loadParser()
    {
        $keywords = $this->getKeywords();

        $lexer = new Lexer($keywords);
        return new Parser($lexer);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig($input);

        $parser = $this->loadParser();

        $finder = new Finder();
        $finder->files()->in($this->featuresDirectory)->name('*.feature');
        /** @var \Symfony\Component\Finder\SplFileInfo $featureFile */
        foreach($finder as $featureFile)
        {
            $featureNode = $parser->parse($featureFile->getContents(), $featureFile->getRealPath());
            if($featureNode instanceof FeatureNode)
            {
                $pathname = $this->extractPathname($featureFile);
                $this->extractDirectory($featureFile, $featureNode);
                $this->features[$pathname] = $featureNode;

                $scenarios = $featureNode->getScenarios();
                $this->extractTags($featureNode, $scenarios);
            }
        }

        $this->sortDirectories();
        $this->sortTags();

        $this->emptyOutputDirectory();
        $this->renderViews();
    }

    protected function sortTags()
    {
        $this->tags = array_count_values($this->tags);
        arsort($this->tags);
    }

    protected function sortDirectories()
    {
        ksort($this->directories);
    }

    protected function extractTags(FeatureNode $featureNode, $scenarios)
    {
        $this->tags = array_merge($this->tags, $featureNode->getTags());
        foreach($scenarios AS $scenario)
        {
            $this->tags = array_merge($this->tags, $scenario->getTags());
        }
    }

    /**
     * @param SplFileInfo $featureFile
     */
    protected function extractDirectory(SplFileInfo $featureFile, FeatureNode $featureNode)
    {
        $filename  = $this->extractFilename($featureNode);
        $filename  = str_replace('.feature', '.html', $filename);
        $directory = $featureFile->getPath();
        $directory = str_replace($this->featuresDirectory . DIRECTORY_SEPARATOR, '', $directory);
        $directory = str_replace(DIRECTORY_SEPARATOR, '/', $directory);

        $this->directories[$directory][$filename] = $featureNode;
    }

    /**
     * @param SplFileInfo $featureFile
     *
     * @return mixed|string
     */
    protected function extractPathname(SplFileInfo $featureFile)
    {
        $pathname = $featureFile->getPathname();
        $pathname = str_replace($this->featuresDirectory . DIRECTORY_SEPARATOR, '', $pathname);
        if(DIRECTORY_SEPARATOR != '/')
        {
            $pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname);
        }
        $pathname = str_replace('.feature', '.html', $pathname);
        return $pathname;
    }

    /**
     * @param FeatureNode $featureNode
     *
     * @return mixed
     */
    protected function extractFilename(FeatureNode $featureNode)
    {
        $directory = $featureNode->getFile();
        $parts     = explode(DIRECTORY_SEPARATOR, $directory);
        return array_pop($parts);
    }

    protected function emptyOutputDirectory()
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->outputDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach($files as $fileinfo)
        {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

    protected function renderViews()
    {
        $viewsDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
        $loader         = new Twig_Loader_Filesystem($viewsDirectory, ['cache' => '/cache',]);
        $twig           = new Twig_Environment($loader);

        $globalTemplateVariables = ['projectName' => $this->projectName];

        $templateVariables = [
            'features'    => $this->features,
            'tags'        => $this->tags,
            'directories' => $this->directories
        ];

        $rendered    = $twig->render('base.html.twig', array_merge($globalTemplateVariables, $templateVariables));
        $filePointer = fopen($this->outputDirectory . 'index.html', 'w');
        fwrite($filePointer, $rendered);

        $directoryVariables = $globalTemplateVariables;
        $featureVariables   = $globalTemplateVariables;
        foreach($this->directories AS $directory => $features)
        {
            $directoryVariables['directory'] = $directory;
            $directoryVariables['features']  = $features;
            $featureVariables['directory']   = $directory;

            $rendered = $twig->render('directory.html.twig', $directoryVariables);
            $path     = $this->outputDirectory . 'directories' . DIRECTORY_SEPARATOR . $directory;
            if(!is_dir($path))
            {
                mkdir($path, null, true);
            }
            $filePointer = fopen($path . DIRECTORY_SEPARATOR . 'index.html', 'w');
            fwrite($filePointer, $rendered);

            foreach($features AS $filename => $featureNode)
            {
                $featureVariables['feature'] = $featureNode;

                $rendered    = $twig->render('feature.html.twig', $featureVariables);
                $filePointer = fopen($path . DIRECTORY_SEPARATOR . $filename, 'w');
                fwrite($filePointer, $rendered);
            }
        }
    }
}
