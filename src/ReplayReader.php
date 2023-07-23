<?php
declare(strict_types=1);

namespace PhpStunts;

class ReplayReader
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
        "trackName = Z8",
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
    public static function load(string $filename) : Replay
    {
        if (!is_readable($filename)) {
            throw new InvalidReplayException("Cannot read replay file: {$filename}");
        }
        return self::decode(file_get_contents($filename) ?: '');
    }

    /**
     * Load a replay from a data string and return a Replay object.
     *
     * @param string $replayData  The replay data to load
     * @return Replay  The instance wrapping the replay
     */
    public static function decode(string $replayData) : Replay
    {
        $header = self::extractHeader($replayData);

        $trackData = self::extractTrack($replayData, $header->version);
        $track = Track::decode($trackData);
        $track->name = $header->trackName;

        $recordingInfo = self::extractRecording($replayData, $header->version);

        return new Replay($header, $track, $recordingInfo);
    }

    /**
     * Unpack the header of a replay and return the decoded details.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @return object  The decoded replay details
     */
    public static function extractHeader($replayData) : object
    {
        $data = self::unpack(self::FIELDS_HEADER, substr($replayData, 0, 0x16));
        $data['version'] = self::detectVersion($replayData);
        return (object) $data;
    }

    /**
     * Return the version of the supplied replay data, or false if the data is
     * not a valid replay.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @return string  One of VERSION_10, VERSION_11
     */
    private static function detectVersion($replayData) : string
    {
        $size = strlen($replayData);

        if ($size < self::MIN_SIZE) {
            throw new InvalidReplayException(
                "The replay data is too small: {$size} < " . self::MIN_SIZE
            );
        }

        if ($size > self::MAX_SIZE) {
            throw new InvalidReplayException(
                "The replay data is too large: {$size} > " . self::MAX_SIZE
            );
        }

        $info = self::unpack(self::FIELDS_VERSION, substr($replayData, 0x16, 0x4));

        $variableSize = $size - 0x70A;

        if ($info['lengthVersion11'] == $variableSize - 0x1A) {
            return Replay::VERSION_11;
        }

        if ($info['lengthVersion10'] == $variableSize - 0x18) {
            return Replay::VERSION_10;
        }

        throw new InvalidReplayException('The replay data is invalid');
    }

    /**
     * Extract the track data from a replay.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @param string $version  The version of the replay file
     * @return string  The track data (1802 bytes)
     */
    public static function extractTrack(string $replayData, string $version)
    {
        $trackPosition = $version == Replay::VERSION_10 ? 0x18 : 0x1A;
        return substr($replayData, $trackPosition, 0x70A);
    }

    /**
     * Extract keyboard data from a replay.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @param string $version  The version of the replay file
     * @return ReplayRecording  A record including the keyboard events and timing
     *                information.
     */
    public static function extractRecording(string $replayData, string $version) : ReplayRecording
    {
        $trackPosition = $version == Replay::VERSION_10 ? 0x18 : 0x1A;
        $keyboardEvents = substr($replayData, $trackPosition + 0x70A);
        $details = self::extractRecordingHeader($replayData, $version);

        $eventCount = strlen($keyboardEvents);
        if ($details['length'] !== $eventCount) {
            throw new InvalidReplayException(
                "Replay length should be {$details['length']}, actually {$eventCount}"
            );
        }

        return new ReplayRecording($keyboardEvents, $details['granularity']);
    }

    /**
     * Extract details about the replay recording from its header.
     *
     * @param string $replayData  Data retrieved from a replay file
     * @param string $version  The version of the replay file
     * @return mixed[]  An associative array specifying granularity and length
     */
    private static function extractRecordingHeader(string $replayData, string $version) : array
    {
        $format = $version == Replay::VERSION_10
            ? [ 'length = v1' ]
            : [ 'granularity = v1', 'length = v1' ];

        return array_merge(
            [ 'granularity' => 20 ],
            self::unpack($format, substr($replayData, 0x16, 0x4))
        );
    }

    /**
     * Unpack a data string into the preset fields.
     *
     * @param array<string> $fields  An array of field defininitions
     * @param string $data  The data to unpack
     * @return mixed[]  The unpacked data
     */
    private static function unpack(array $fields, string $data)
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
     * @param array<string> $fields  An array of field defininitions
     * @return string  The pack() compatible string
     */
    private static function joinFields(array $fields) : string
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
