<?php

namespace FeatureBrowser\FeatureBrowser;

use FeatureBrowser\FeatureBrowser\Cli\Application;

/**
 * Class ApplicationFactory
 *
 * @package FeatureBrowser
 */
final class ApplicationFactory
{
    const VERSION = '0.0.1';

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'Feature Browser';
    }

    /**
     * {@inheritdoc}
     */
    protected function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Creates application instance.
     *
     * @return Application
     */
    public function createApplication()
    {
        return new Application($this->getName(), $this->getVersion());
    }
}
