<?php

declare(strict_types=1);

namespace CC\Shared;

final class AverageExecution
{
    private $f;
    private $average;

    public static function lift(callable $f): AverageExecution
    {
        return new self($f);
    }

    public function __invoke($x)
    {
        $start = \microtime(true);

        $result = ($this->f)($x);

        $this->average->push(\microtime(true) - $start);

        return $result;
    }

    public function __call($name, $arguments)
    {
        return ($this->f)->{$name}(...$arguments);
    }

    public function average(): StreamAverage
    {
        return $this->average;
    }

    private function __construct(callable $f)
    {
        $this->f = $f;
        $this->average = StreamAverage::zero();
    }
}
