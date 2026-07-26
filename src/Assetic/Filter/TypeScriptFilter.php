<?php

namespace Assetic\Filter;

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Assetic\Util\FilesystemUtils;

/**
 * Compiles TypeScript into JavaScript.
 *
 * @link http://www.typescriptlang.org/
 * @author Jarrod Nettles <jarrod.nettles@icloud.com>
 */
class TypeScriptFilter extends BaseNodeFilter
{
    /**
     * @var string Path to the binary for this process based filter
     */
    protected $binaryPath = '/usr/bin/tsc';

    /**
     * {@inheritDoc}
     */
    protected function getInputPath(string $input)
    {
        $prefix = preg_replace('/[^\w]/', '', static::class);
        $path = FilesystemUtils::createThrowAwayDirectory($prefix) . '/input.ts';
        file_put_contents($path, $input);
        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
        // Newer TypeScript releases removed the single-file `--outFile` option, so
        // emit into an output directory and read the compiled file back. The input
        // is always written as `input.ts` (see getInputPath()), so tsc produces
        // `input.js` inside the output directory.
        $args = [
            '{INPUT}',
            '--outDir',
            '{OUTPUT}',
        ];

        $result = $this->runProcess($asset->getContent(), $args);
        $asset->setContent($result);
    }

    /**
     * {@inheritDoc}
     *
     * tsc emits into a directory rather than a single named file, so the output
     * location must be a throw-away directory instead of a temporary file.
     */
    protected function getOutputPath()
    {
        $prefix = preg_replace('/[^\w]/', '', static::class);
        return FilesystemUtils::createThrowAwayDirectory($prefix . '-output');
    }

    /**
     * {@inheritDoc}
     *
     * Reads the compiled `input.js` from the output directory produced by tsc.
     */
    protected function getOutput()
    {
        return file_get_contents($this->outputPath . '/input.js');
    }
}
