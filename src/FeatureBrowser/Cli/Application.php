<?php

namespace FeatureBrowser\FeatureBrowser\Cli;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * Extends Symfony console application
 */
final class Application extends BaseApplication
{
    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[] An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands   = parent::getDefaultCommands();
        $defaultCommands[] = new GenerateCommand();
        return $defaultCommands;
    }

}
