<?php

namespace Assetic\Extension\Twig;

use Twig\TwigFunction;

class AsseticFilterFunction
{
    public static function make(AsseticExtension $extension, $name, $options = [])
    {
        $options = array_merge($options, [
            'needs_environment' => false,
            'needs_context'     => false,
            'node_class'        => AsseticFilterNode::class,
        ]);

        return new TwigFunction($name, function ($input, array $options) use ($extension, $name) {
            return $extension->getFilterInvoker($name)->invoke($input, $options);
        }, $options);
    }
}
