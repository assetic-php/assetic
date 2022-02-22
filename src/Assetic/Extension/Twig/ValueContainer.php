<?php namespace Assetic\Extension\Twig;

use Traversable;
use Assetic\Contracts\ValueSupplierInterface;

/**
 * Container for values initialized lazily from a ValueSupplierInterface.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ValueContainer implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private $values;
    private $valueSupplier;

    public function __construct(ValueSupplierInterface $valueSupplier)
    {
        $this->valueSupplier = $valueSupplier;
    }

    public function offsetExists($offset): bool
    {
        $this->initialize();

        return array_key_exists($offset, $this->values);
    }

    // Return type should change to :mixed as soon as PHP 8.0 is the lowest version targeted
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $this->initialize();

        if (!array_key_exists($offset, $this->values)) {
            throw new \OutOfRangeException(sprintf('The variable "%s" does not exist.', $offset));
        }

        return $this->values[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('The ValueContainer is read-only.');
    }

    // Return type should change to :void (and mixed $offset for parameter type
    // hint) as soon as PHP 8.0 is the lowest version targeted
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('The ValueContainer is read-only.');
    }

    public function getIterator(): Traversable
    {
        $this->initialize();

        return new \ArrayIterator($this->values);
    }

    public function count(): int
    {
        $this->initialize();

        return count($this->values);
    }

    private function initialize()
    {
        if (null === $this->values) {
            $this->values = $this->valueSupplier->getValues();
        }
    }
}
