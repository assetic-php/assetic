<?php

namespace Assetic\Factory\Resource;

use Traversable;
use Assetic\Contracts\Factory\Resource\IteratorResourceInterface;
use Assetic\Contracts\Factory\Resource\ResourceInterface;

/**
 * Coalesces multiple directories together into one merged resource.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CoalescingDirectoryResource implements IteratorResourceInterface
{
    private $directories;

    public function __construct($directories)
    {
        $this->directories = [];

        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }
    }

    public function addDirectory(IteratorResourceInterface $directory)
    {
        $this->directories[] = $directory;
    }

    public function isFresh($timestamp)
    {
        foreach ($this->getFileResources() as $file) {
            if (!$file->isFresh($timestamp)) {
                return false;
            }
        }

        return true;
    }

    public function getContent()
    {
        $parts = [];
        foreach ($this->getFileResources() as $file) {
            $parts[] = $file->getContent();
        }

        return implode("\n", $parts);
    }

    /**
     * Returns a string to uniquely identify the current resource.
     *
     * @return string An identifying string
     */
    public function __toString()
    {
        $parts = [];
        foreach ($this->directories as $directory) {
            $parts[] = (string) $directory;
        }

        return implode(',', $parts);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->getFileResources());
    }

    /**
     * Returns the relative version of a filename.
     *
     * @param ResourceInterface $file      The file
     * @param ResourceInterface $directory The directory
     *
     * @return string The name to compare with files from other directories
     */
    protected function getRelativeName(ResourceInterface $file, ResourceInterface $directory)
    {
        return substr((string) $file, strlen((string) $directory));
    }

    /**
     * Performs the coalesce.
     *
     * @return array An array of file resources
     */
    private function getFileResources()
    {
        $paths = [];

        foreach ($this->directories as $directory) {
            foreach ($directory as $file) {
                $relative = $this->getRelativeName($file, $directory);

                if (!isset($paths[$relative])) {
                    $paths[$relative] = $file;
                }
            }
        }

        return array_values($paths);
    }
}
