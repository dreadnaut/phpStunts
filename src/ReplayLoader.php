<?php

namespace phpStunts;

class ReplayLoader
{

    /**
     * Field definitions for unpacking the header.
     */
    const FIELDS_HEADER = [
        "playerCar = A4",
        "playerColor = C1",
        "playerTransmission = C1",
        "opponentType = C1",
        "opponentCar = A4",
        "opponentColor = C1",
        "opponentTransmission = C1",
        "trackName = a8",
        "x",
    ];

    /**
     * Field definitions for detecting the replay version.
     */
    const FIELDS_VERSION = [
        'lengthVersion10 = v1',
        'lengthVersion11 = v1',
    ];

    /**
     * Minimum and maximum replay size accepted, in bytes.
     */
    const MIN_SIZE = 0x18 + 0x70A;
    const MAX_SIZE = 0x3fff;

    /**
     * Load a replay from file and return a Replay instance.
     *
     * @param string $filename  The replay to load
     * @return Replay  The instance wrapping the replay
     */
    public static function fromFile($filename)
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException("Cannot read replay file {$filename}");
        }

        $replaySize = filesize($filename);

        if ($replaySize > self::MAX_SIZE) {
            throw new \LengthException("The replay file exceeds maximum size limit of " . self::MAX_SIZE);
        }

        $data = file_get_contents($filename);

        if ($data === false) {
            throw new \Exception("Error reading replay file {$filename}");
        }

        return self::fromData($data);
    }

    /**
     * Load a replay from a data string and return a Replay object.
     *
     * @param string $replayData  The replay data to load
     * @return Replay  The instance wrapping the replay
     */
    public static function fromData($replayData)
    {
        $version = self::detectVersion($replayData);

        if (!$version) {
            throw new \InvalidArgumentException("The replay data is invalid");
        }

        $header = self::unpackHeader($replayData);
        $trackInfo = self::extractTrack($replayData, $version);
        $recordingInfo = self::extractRecording($replayData, $version);

        $header['version'] = $version;

        return new Replay($header, $trackInfo, $recordingInfo);
    }

    /**
     * Return the version of the supplied replay data, or false if the data is
     * not a valid replay.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @return string|false  One of VERSION_!0, VERSION_11, or false
     */
    public static function detectVersion($replayData)
    {
        $size = strlen($replayData);

        // if the file is too small, it can't be a replay
        if ($size < self::MIN_SIZE) {
            return false;
        }

        $info = self::unpack(self::FIELDS_VERSION, substr($replayData, 0x16, 0x4));

        $variableSize = $size - 0x70A;

        if ($info['lengthVersion11'] + 0x1A == $variableSize) {
            return Replay::VERSION_11;
        }

        if ($info['lengthVersion10'] + 0x18 == $variableSize) {
            return Replay::VERSION_10;
        }

        return false;
    }

    /**
     * Unpack the header of replay and return the decoded details.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @return array  The decoded replay details
     */
    public static function unpackHeader($replayData)
    {
        $header = self::unpack(self::FIELDS_HEADER, substr($replayData, 0, 0x16));

        // the track name is zero-padded, but we want a clean string
        $header['trackName'] = rtrim($header['trackName'], "\0");

        return $header;
    }

    /**
     * Extract the track data from a replay.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @param string $version  The version of the replay file
     * @return string  The track data (1802 bytes)
     */
    public static function extractTrack($replayData, $version)
    {
        $trackPosition = $version == Replay::VERSION_10 ? 0x18 : 0x1A;
        return substr($replayData, $trackPosition, 0x70A);
    }

    /**
     * Extract keyboard data from a replay.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @param string $version  The version of the replay file
     * @return array  A record including the keyboard events and timing
     *                information.
     */
    public static function extractRecording($replayData, $version)
    {
        $headerSize = $version == Replay::VERSION_10 ? 0x1A : 0x1C;

        $info = [
            'granularity' => 20,
            'keyboardEvents' => substr($replayData, $headerSize + 0x70A),
        ];

        $recordingHeader = $version == Replay::VERSION_10
            ? [ 'length = v1' ]
            : [ 'granularity = v1', 'length = v1' ];

        return $info + self::unpack($recordingHeader, substr($replayData, 0x16, 0x4));
    }

    /**
     * Unpack a data string into the preset fields.
     *
     * @param array $fields  An array of field defininitions
     * @param string $data  The data to unpack
     * @return array  The unpacked data
     */
    private static function unpack(array $fields, $data)
    {
        return unpack(self::joinFields($fields), $data);
    }

    /**
     * Convert an array of fields into a string that can be used with the
     * pack() function.
     *
     * Fields are defined as strings "(key) = (pack format characters)"
     * See http://www.php.net/pack for more details.
     *
     * @param array $fields  An array of field defininitions
     * @return string  The pack() compatible string
     */
    private static function joinFields(array $fields)
    {
        $fieldDefs = array_map(
            function ($field) {
                return preg_replace('#^(\w+)\s*=\s*(\w+)$#', "$2$1", $field);
            },
            $fields
        );
        return implode('/', $fieldDefs);
    }
}
