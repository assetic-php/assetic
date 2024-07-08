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
     * Path to the Tailwind configuration file.
     */
    protected ?string $configPath = null;

    /**
     * Is minification enabled?
     */
    protected bool $minify = false;

    /**
     * Is autoprefixing enabled?
     */
    protected bool $autoprefix = true;

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
