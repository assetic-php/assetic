<?php namespace Assetic\Factory\Resource;

/**
 * An iterator that converts file objects into file resources.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @access private
 */
class DirectoryResourceIterator extends \RecursiveIteratorIterator
{
    // Return type should change to :mixed as soon as PHP 8.0 is the lowest version targeted
    #[\ReturnTypeWillChange]
    public function current()
    {
        return new FileResource(parent::current()->getPathname());
    }
}
