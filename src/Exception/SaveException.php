<?php

namespace PhpArsenal\SoapClient\Exception;

use Traversable;

/**
 * Collection of faulty results.
 */
class SaveException extends \Exception implements \IteratorAggregate, \Countable
{
    protected $results = [];

    public function add($result)
    {
        $this->results[] = $result;

        $this->message = implode(
            "\n",
            array_map(
                function ($e) {
                    $errors = $e->getErrors();
                    if (count($errors) > 0) {
                        return $errors[0]->getMessage();
                    }
                },
                $this->results
            )
        );
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->results);
    }

    public function count(): int
    {
        return count($this->results);
    }
}
