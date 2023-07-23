<?php
declare(strict_types=1);

namespace PhpStunts;

class Track
{
    /**
     * Standard values for the track horizon byte.
     */
    const HORIZON_DESERT    = 0;
    const HORIZON_TROPICAL  = 1;
    const HORIZON_ALPINE    = 2;
    const HORIZON_CITY      = 3;
    const HORIZON_COUNTRY   = 4;

    /**
     * Standard value for the reserved byte.
     */
    const RESERVED_DEFAULT = 0;

    /**
     *
     */
    const BASE_SIZE = 1802;
    const MAP_SIZE = 900;

    /**
     *
     */
    public static function load(string $filename) : self
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException("Cannot read track file: {$filename}");
        }

        $track = self::decode(file_get_contents($filename) ?: '');
        $track->name = substr(basename(strtoupper($filename), '.TRK'), 0, 8);
        return $track;
    }

    /**
     *
     */
    public static function decode(string $trackData) : self
    {
        $size = strlen($trackData);

        if ($size < self::BASE_SIZE) {
            throw new InvalidTrackException(
                "The track data is too small: {$size} < " . self::BASE_SIZE
            );
        }
        return new Track(
            layout: substr($trackData, 0, self::MAP_SIZE),
            terrain: substr($trackData, self::MAP_SIZE + 1, self::MAP_SIZE),
            horizon: ord($trackData[self::MAP_SIZE]),
            reserved: ord($trackData[self::BASE_SIZE - 1]),
            appended: substr($trackData, self::BASE_SIZE)
        );
    }

    /**
     *
     */
    public static function empty(int $horizon = self::HORIZON_DESERT) : self
    {
        return new Track(
            layout: str_repeat("\x00", self::MAP_SIZE),
            terrain: str_repeat("\x00", self::MAP_SIZE),
            horizon: $horizon & 255,
            reserved: self::RESERVED_DEFAULT
        );
    }

    /**
     *
     */
    private function __construct(
        public string $layout,
        public string $terrain,
        public int $horizon = self::HORIZON_DESERT,
        public int $reserved = self::RESERVED_DEFAULT,
        public string $appended = '',
        public string $name = '',
    ) {
        $this->validateDimensions();
        $this->horizon = $horizon & 255;
        $this->reserved = $reserved & 255;
    }

    /**
     *
     */
    public function normalize() : self
    {
        return new Track(
            $this->layout,
            $this->terrain,
            $this->horizon,
            reserved: self::RESERVED_DEFAULT,
            appended: ''
        );
    }

    /**
     *
     */
    public function truncate() : self
    {
        return new Track(
            $this->layout,
            $this->terrain,
            $this->horizon,
            $this->reserved,
            appended: ''
        );
    }

    /**
     *
     */
    public function hash() : string
    {
        return sha1($this->encode());
    }

    /**
     *
     */
    public function encode() : string
    {
        return $this->layout . chr($this->horizon) . $this->terrain
            . chr($this->reserved) . $this->appended;
    }

    /**
     *
     */
    public function save(string $filename) : bool
    {
        try {
            $written = file_put_contents($filename, $this->encode());
            return $written !== false;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    /**
     *
     */
    private function validateDimensions() : void
    {
        $layoutSize = strlen($this->layout);
        if ($layoutSize != self::MAP_SIZE) {
            throw new \InvalidArgumentException(
                "Track layout size incorrect: {$layoutSize} < " . self::MAP_SIZE
            );
        }

        $terrainSize = strlen($this->terrain);
        if ($terrainSize != self::MAP_SIZE) {
            throw new \InvalidArgumentException(
                "Track terrain size incorrect: {$terrainSize} < " . self::MAP_SIZE
            );
        }
    }
}
