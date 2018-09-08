<?php

namespace phpStunts;

class TrackLoader
{

    /**
     * Normal track files are precisely 1802 bytes (0x70A). To accept tracks
     * with trailing metadata, such as those produces by the Bliss editor, we
     * set a higher limit and load extra data in a separate property.
     */
    private const BASE_SIZE = 1802;
    private const MAX_SIZE = self::BASE_SIZE + 256;

    /**
     * Load a track from disc and return the Track object.
     *
     * @param string $filename  The track file to load
     * @return Track  An object wrapping the track's details
     */
    public static function fromFile($filename)
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException("Cannot read track file {$filename}");
        }

        $replaySize = filesize($filename);

        if ($replaySize < self::BASE_SIZE) {
            throw new \LengthException("The track file {$filename} is too small at {$replaySize}");
        }

        if ($replaySize > self::MAX_SIZE) {
            throw new \LengthException("The track file {$filename} exceeds maximum size limit of " . self::MAX_SIZE);
        }

        $trackData = file_get_contents($filename);
        if (!$trackData) {
            throw new \Exception("Cannot load the contents of {$filename}");
        }

        $track = self::fromData($trackData);

        return $track;
    }

    /**
     * Load a track from a data string and return a Track object.
     *
     * @param string $data  The track data to load
     * @return Track  An object wrapping the track's details
     */
    public static function fromData($data)
    {
        return new Track(
            substr($data, 0, 900),
            substr($data, 901, 900),
            ord($data[900]),
            ord($data[1801]),
            substr($data, 1802)
        );
    }
}
