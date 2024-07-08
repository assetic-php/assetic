<?php

namespace Assetic\Filter;

use Assetic\Contracts\Asset\AssetInterface;

/**
 * Tailwind CSS filter.
 *
 * Compiles Tailwind CSS into standard CSS, using the standalone Tailwind CSS CLI tool.
 *
 * @author Ben Thomson <git@alfreido.com>
 */
class TailwindCssFilter extends BaseProcessFilter
{
    /**
     * @var string Path to the binary for this process based filter
     */
    protected $binaryPath = '/usr/bin/tailwindcss';

    /**
     * @var string|null Path to the Tailwind configuration file.
     */
    protected $configPath = null;

    /**
     * @var bool Is minification enabled?
     */
    protected $minify = false;

    /**
     * @var bool Is autoprefixing enabled?
     */
    protected $autoprefix = true;

    /**
     * Sets the path for the configuration file.
     */
    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;
    }

    /**
     * Enable minification.
     */
    public function minify(): void
    {
        $this->minify = true;
    }

    /**
     * Disable autoprefixer.
     */
    public function withoutAutoprefixing(): void
    {
        $this->autoprefix = false;
    }

    /**
     * {@inheritDoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
        $args = [
            '--input',
            '{INPUT}',
            '--output',
            '{OUTPUT}'
        ];

        if (!is_null($this->configPath)) {
            $args[] = '--config';
            $args[] = $this->configPath;
        }

        if ($this->minify) {
            $args[] = '--minify';
        }

        if (!$this->autoprefix) {
            $args[] = '--no-autoprefixer';
        }

        $result = $this->runProcess($asset->getContent(), $args);
        $asset->setContent($result);
    }
}
