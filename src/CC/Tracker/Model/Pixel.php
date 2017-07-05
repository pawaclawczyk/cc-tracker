<?php

declare(strict_types=1);

namespace CC\Tracker\Model;

final class Pixel
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __toString(): string
    {
        return $this->data;
    }
}
