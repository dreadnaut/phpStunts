<?php
declare(strict_types=1);

namespace PhpStunts;

class Replay
{
    /**
     * Replay version symbols, not part of the file format.
     */
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';

    public readonly object $car;
    public readonly int $opponent;
    public readonly ?object $opponentCar;
    public readonly int $time;
    public readonly string $version;

    public static function load(string $filename) : self
    {
        return ReplayReader::load($filename);
    }

    public static function decode(string $replayData) : self
    {
        return ReplayReader::decode($replayData);
    }

    public function __construct(
        object $header,
        public readonly Track $track,
        public readonly ReplayRecording $recording,
    ) {
        $this->car = new Car(
            $header->playerCar,
            $header->playerColor,
            $header->playerTransmission,
        );
        $this->opponent = $header->opponentType;
        $this->opponentCar = $this->opponent === 0
            ? null
            : new Car(
                $header->opponentCar,
                $header->opponentColor,
                $header->opponentTransmission
            );
        $this->time = $recording->seemsComplete()
            ? $recording->time - 100
            : $recording->time;
        $this->version = $header->version;
    }
}
