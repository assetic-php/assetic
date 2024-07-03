<?php

namespace Assetic\Extension\Twig;

use Twig\Compiler;
use Twig\Node\Expression\FunctionExpression;

class AsseticFilterNode extends FunctionExpression
{
    protected function compileCallable(Compiler $compiler)
    {
        $compiler->raw(
            sprintf(
                '$this->env->getExtension(\'Assetic\\Extension\\Twig\\AsseticExtension\')->getFilterInvoker(\'%s\')->invoke',
                $this->getAttribute('name')
            )
        );

        $this->compileArguments($compiler);
    }
}
