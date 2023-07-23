<?php
declare(strict_types=1);

namespace PhpStunts;

class ReplayRecording
{
    public readonly int $time;

    public function __construct(
        public readonly string $keyboardEvents,
        public readonly int $granularity = 20
    ) {
        $this->time = strlen($keyboardEvents) * 100 / $granularity;
    }

    public function seemsComplete() : bool
    {
        $lastSecond = substr($this->keyboardEvents, 0 - $this->granularity);
        return strlen(trim($lastSecond, "\0")) === 0;
    }
}
