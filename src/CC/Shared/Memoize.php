<?php

declare(strict_types=1);

namespace CC\Shared;

use Ds\Map;

final class Memoize
{
    private $f;
    private $cache;
    private $hits;

    public static function lift(callable $f)
    {
        return new self($f);
    }

    public function __invoke($x)
    {
        $serializedArgs = \json_encode($x);

        if ($this->cache->hasKey($serializedArgs)) {
            ++$this->hits;

            return $this->cache->get($serializedArgs);
        }

        $result = ($this->f)($x);

        $this->cache->put($serializedArgs, $result);

        return $result;
    }

    public function __call($name, $arguments)
    {
        return ($this->f)->{$name}(...$arguments);
    }

    public function hits(): int
    {
        return $this->hits;
    }

    private function __construct(callable $f)
    {
        $this->f = $f;
        $this->cache = new Map();
        $this->hits = 0;
    }
}
