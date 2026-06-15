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
     * @var int|null The installed major version of the Tailwind CSS CLI utility, as an integer (eg. 3 for v3.*, 4 for v4.*). Cached after the first call to getVersion().
     */
    protected static $cliVersion = null;

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
        $args = ['--input', '{INPUT}', '--output', '{OUTPUT}'];

        $content = $asset->getContent();

        if (!is_null($this->configPath)) {
            if ($this->getVersion() === 4) {
                $content = '@config "' . $this->configPath . '";' . PHP_EOL . $content;
            } else {
                $args[] = '--config';
                $args[] = $this->configPath;
            }
        }

        if ($this->minify) {
            $args[] = '--minify';
        }

        if ($this->getVersion() === 4) {
            $args[] = '--cwd';
            $args[] = $this->workingDirectory ?? (!is_null($this->configPath) ? dirname($this->configPath) : null);
        }

        if ($this->getVersion() === 3 && !$this->autoprefix) {
            $args[] = '--no-autoprefixer';
        }

        $result = $this->runProcess($content, $args);
        $asset->setContent($result);
    }

    /**
     * Gets the installed version of the Tailwind CSS CLI utility.
     */
    protected function getVersion(): int
    {
        // We don't expect the binary version to change mid-execution
        if (!is_null(self::$cliVersion)) {
            return self::$cliVersion;
        }

        $process = $this->createProcess(array_merge($this->getPathArgs(), ['--help']));
        $code = $process->run();

        if ($code !== 0) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to execute "%s --help" to determine Tailwind CSS CLI version: %s',
                    $this->binaryPath,
                    $process->getErrorOutput(),
                ),
            );
        }

        $output = trim($process->getOutput());
        if (!preg_match('/^[^\n\r]*tailwindcss +v([0-9])\./i', $output, $matches) || !isset($matches[1])) {
            throw new \RuntimeException('Unable to find version identifier in Tailwind CSS CLI utility');
        }

        self::$cliVersion = (int) $matches[1] === 4 ? 4 : 3;

        return self::$cliVersion;
    }
}
