<?php
declare(strict_types=1);

namespace PhpStunts;

class Car
{
    public readonly string $transmissionName;

    public function __construct(
        public readonly string $name,
        public readonly int $color,
        public readonly int $transmission,
    ) {
        $this->transmissionName = $transmission ? 'automatic' : 'manual';
    }
}
