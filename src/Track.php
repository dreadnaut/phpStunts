<?php

namespace phpStunts;

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
     * Size in bytes for each of the track maps.
     */
    const SIZE_LAYOUT = 900;
    const SIZE_TERRAIN = 900;

    /**
     * @var string  Road and scenery data.
     */
    private $layout;

    /**
     * @var int  One of the predefined horizon types.
     */
    private $horizon;

    /**
     * @var string  Terrain data.
     */
    private $terrain;

    /**
     * @var int  Reserved byte. Usually zero, but non standard values are used
     *           by custom editors.
     */
    private $reserved;

    /**
     * @var string  Non-standard data found at the end of the track file.
     */
    private $extra;

    /**
     * Create a new track with the specified horizon and a flat terrain.
     *
     * @param int $horizon  The horizon to use for the track
     * @return Track  The new Track object
     */
    public static function empty($horizon = self::HORIZON_DESERT)
    {
        return new self(
            str_repeat("\x00", self::SIZE_LAYOUT),
            str_repeat("\x00", self::SIZE_TERRAIN),
            $horizon
        );
    }

    /**
     * Create a new instance and store the given track data.
     *
     * @param string $layout   900 bytes representing the track layout
     * @param string $terrain  900 bytes representing the track terrain
     * @param int $horizon     The horizon to use for the track
     * @param int $reserved    The value of the track's reserved byte
     * @param string $extra    Trailing data outside the usual file format
     */
    public function __construct(
        $layout,
        $terrain,
        $horizon = self::HORIZON_DESERT,
        $reserved = self::RESERVED_DEFAULT,
        $extra = ''
    ) {
        $this->layout = substr($layout, 0, self::SIZE_LAYOUT);
        $this->terrain = substr($terrain, 0, self::SIZE_TERRAIN);
        $this->horizon = $horizon & 255;
        $this->reserved = $reserved & 255;
        $this->extra = $extra;
    }

    /**
     * Return the track layout data.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Return the track horizon type.
     *
     * @return int
     */
    public function getHorizon()
    {
        return $this->horizon;
    }

    /**
     * Return the track terrain data.
     *
     * @return string
     */
    public function getTerrain()
    {
        return $this->terrain;
    }

    /**
     * Return the reserved byte of the track.
     *
     * @return int
     */
    public function getReserved()
    {
        return $this->reserved;
    }

    /**
     * Return the trailing data of the file.
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Return the track data as stored.
     *
     * This might include a non-standard reserved byte and trailing data beyond
     * 1802 bytes.
     *
     * @return string
     */
    public function getOriginalData()
    {
        return $this->layout . chr($this->horizon) . $this->terrain
            . chr($this->reserved) . $this->extra;
    }

    /**
     * Return the track data as it would be saved by Stunts.
     *
     * The reserved byte is set to zero, and there is no trailing data.
     *
     * @return string[1802]
     */
    public function getData()
    {
        return $this->layout . chr($this->horizon) . $this->terrain . "\x00";
    }

    /**
     * Return the track hash, calculated on the canonical data.
     *
     * @return string[40]
     */
    public function getHash()
    {
        return sha1($this->getData());
    }
}
