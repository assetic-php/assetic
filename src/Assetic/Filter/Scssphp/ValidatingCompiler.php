<?php

namespace Assetic\Filter\Scssphp;

use ScssPhp\ScssPhp\Compiler;

/**
 * A scssphp compiler that runs every resolved `@import` target past a validator
 * before the file is read.
 *
 * scssphp resolves `@import` targets against the compiler's import paths and, for
 * nested imports, against the importing file's own directory — both with `..`
 * traversal allowed. Consumers that need resolution bounded to a particular tree
 * therefore need a hook; this compiler provides it by filtering the result of
 * {@see Compiler::findImport()}.
 *
 * A rejected import is reported as unresolved, which makes scssphp emit the
 * original `@import` statement verbatim instead of inlining the file — the same
 * outcome as {@see \Assetic\Filter\CssImportFilter}'s rejected imports.
 *
 * @internal
 */
class ValidatingCompiler extends Compiler
{
    /** @var callable */
    private $importValidator;

    public function __construct(callable $importValidator)
    {
        parent::__construct();

        $this->importValidator = $importValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function findImport($url, $currentDir = null)
    {
        $path = parent::findImport($url, $currentDir);

        if (null === $path) {
            return null;
        }

        return call_user_func($this->importValidator, $path) ? $path : null;
    }
}
