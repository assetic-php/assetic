<?php namespace Assetic\Extension\Twig;

use Twig\Node\Expression\FunctionExpression;
use Twig\Compiler;

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
