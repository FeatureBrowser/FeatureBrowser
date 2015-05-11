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
use Twig_Extension_Debug;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;


final class GenerateCommand extends BaseCommand
{
    protected $configFile  = 'featurebrowser.yml';
    protected $projectName;
    protected $baseUrl;
    protected $outputDirectory;
    protected $featuresDirectory;
    protected $features    = [];
    protected $tags        = [];
    protected $tagsTree    = [];
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
        $configFile = $this->configFile;
        if(!is_file($configFile))
        {
            $configFile = $this->configFile . '.dist';
        }
        if(!is_file($configFile))
        {
            throw new \Exception("Cannot locate `featurebrowser.yml` config file");
        }
        $configs                 = Yaml::parse($configFile);
        $this->projectName       = $configs['featurebrowser']['project-name'];
        $this->featuresDirectory = $configs['featurebrowser']['features-directory'];
        $this->baseUrl           = $configs['featurebrowser']['base-url'];

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
                $this->extractTags($featureNode, $scenarios, $pathname);
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

    /**
     * @param FeatureNode $featureNode
     * @param             $scenarios
     */
    protected function extractTags(FeatureNode $featureNode, $scenarios, $pathname)
    {
        $featureTags = $featureNode->getTags();
        $this->tags  = array_merge($this->tags, $featureTags);
        foreach($featureTags AS $tag)
        {
            $this->tagsTree[$tag]['features'][$pathname] = $featureNode;
        }
        foreach($scenarios AS $scenario)
        {
            $scenarioTags = $scenario->getTags();
            $this->tags   = array_merge($this->tags, $scenarioTags);
            foreach($scenarioTags AS $tag)
            {
                $this->tagsTree[$tag]['scenarios'][$pathname][] = ['scenario' => $scenario, 'feature' => $featureNode];
            }
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

    /**
     *
     */
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

    /**
     * Render all the html views
     */
    protected function renderViews()
    {
        $viewsDirectory       = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
        $macrosDirectory      = $viewsDirectory . DIRECTORY_SEPARATOR . 'macros';
        $templatesDirectories = [$viewsDirectory, $macrosDirectory];
        $loader               = new Twig_Loader_Filesystem($templatesDirectories, ['cache' => '/cache']);
        $this->twig           = new Twig_Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new Twig_Extension_Debug());
        $this->twig->addGlobal('projectName', $this->projectName);
        $this->twig->addGlobal('baseDirectory', $this->outputDirectory);
        $this->twig->addGlobal('baseUrl', $this->baseUrl);

        $this->renderBaseView();

        $directoryVariables = [];
        $featureVariables   = [];
        foreach($this->directories AS $directory => $features)
        {
            $path = $this->makeOutputDir('directories' . DIRECTORY_SEPARATOR . $directory);
            $this->renderDirectoryView($directory, $features, $path, $directoryVariables);

            $featureVariables['directory'] = $directory;
            foreach($features AS $filename => $featureNode)
            {
                $this->renderFeatureView($featureNode, $path, $filename, $featureVariables);
            }
        }
        $path = $this->makeOutputDir('tags');
        foreach($this->tagsTree AS $tag => $usage)
        {
            $this->renderTagView($tag, $usage, $path);
        }
    }

    /**
     * @param $directory
     *
     * @return string
     */
    protected function makeOutputDir($directory)
    {
        $path = $this->outputDirectory . $directory;
        if(!is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * @param $globalTemplateVariables
     */
    protected function renderBaseView()
    {
        $baseTemplateVariables = [
            'features'    => $this->features,
            'tags'        => $this->tags,
            'directories' => $this->directories
        ];

        $rendered    = $this->twig->render('base.html.twig', $baseTemplateVariables);
        $filePointer = fopen($this->outputDirectory . 'index.html', 'w');

        fwrite($filePointer, $rendered);
    }

    /**
     * @param $featureNode
     * @param $path
     * @param $filename
     */
    protected function renderFeatureView($featureNode, $path, $filename, $featureVariables)
    {
        $featureVariables['feature'] = $featureNode;

        $rendered    = $this->twig->render('feature.html.twig', $featureVariables);
        $filePointer = fopen($path . DIRECTORY_SEPARATOR . $filename, 'w');
        fwrite($filePointer, $rendered);
    }

    protected function renderTagView($tag, $usage, $path)
    {
        $filename = $path . DIRECTORY_SEPARATOR . $tag . '.html';

        $templateVariables['tag'] = $tag;
        foreach($usage AS $type => $nodes)
        {
            $templateVariables[$type] = $nodes;
        }

        $rendered    = $this->twig->render('tag.html.twig', $templateVariables);
        $filePointer = fopen($filename, 'w');
        fwrite($filePointer, $rendered);
    }

    /**
     * @param $directory
     * @param $features
     */
    protected function renderDirectoryView($directory, $features, $path, $directoryVariables)
    {
        $directoryVariables['directory'] = $directory;
        $directoryVariables['features']  = $features;

        $rendered    = $this->twig->render('directory.html.twig', $directoryVariables);
        $filePointer = fopen($path . DIRECTORY_SEPARATOR . 'index.html', 'w');
        fwrite($filePointer, $rendered);
    }
}
