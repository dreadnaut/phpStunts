<?php

namespace phpStunts;

class Replay
{

    /**
     * Names of the standard opponents.
     */
    const OPPONENTS = [ 'None', 'Bernie', 'Otto', 'Joe', 'Cherry', 'Helen', 'Skid' ];

    /**
     * Values representing the opponents in the replay format.
     */
    const OPPONENT_NONE   = 0;
    const OPPONENT_BERNIE = 1;
    const OPPONENT_OTTO   = 2;
    const OPPONENT_JOE    = 3;
    const OPPONENT_CHERRY = 4;
    const OPPONENT_HELEN  = 5;
    const OPPONENT_SKID   = 6;

    /**
     * Value used for the opponent's car name when no opponents is selected.
     */
    const NO_OPPONENT_CAR = "\xff";

    /**
     * Replay version symbols, not part of the file format.
     */
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';

    /**
     * @var array  Information extracted from the header of the replay file
     *             (car, opponent, track name).
     */
    private $header;

    /**
     * @var array  Keyboard data and timing information extracted from the
     *             replay.
     */
    private $recording;

    /**
     * @var string  The track data extracted from the replay.
     */
    private $trackData;

    /**
     * @var Track|null  Caches the Track object for the replay track.
     */
    private $trackInstance;

    /**
     * @var boolean  True if the replay appears to have been interrupted before
     *               the finish line.
     */
    private $isIncomplete;

    /**
     * @var float  Duration of the replay, in seconds.
     */
    private $recordedTime;

    /**
     * @var float  Estimated duration of the lap, in seconds.
     */
    private $time;

    /**
     * Creates a new istance from data unpacked from a replay file.
     *
     * @param array $header      The unpacked header of a replay
     * @param string $track      Track data
     * @param array $recording   Granularity and keyboard data
     */
    public function __construct(array $header, $track, array $recording)
    {
        $this->header = $header;
        $this->trackData = substr($track, 0, 0x70A);
        $this->recording = $recording;

        $this->recordedTime = $recording['length'] / $recording['granularity'];

        $this->isIncomplete = $this->hasLastSecondActivity(
            $recording['keyboardEvents'],
            $recording['granularity']
        );

        // if the replay seems complete, we remove one second from recording time
        $this->time = $this->isIncomplete
            ? $this->recordedTime
            : $this->recordedTime - 1;
    }

    /**
     * Return the Stunts version of the replay.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->header['version'];
    }

    /**
     * Return an object with information about the player's car.
     *
     * @return object
     */
    public function getCar()
    {
        return (object) [
            'name'         => $this->header['playerCar'],
            'color'        => $this->header['playerColor'],
            'transmission' => $this->header['playerTransmission'],
        ];
    }

    /**
     * Return true if the replay included an opponent.
     *
     * @return boolean
     */
    public function hasOpponent()
    {
        return $this->header['opponentType'] != self::OPPONENT_NONE;
    }

    /**
     * Return an object with the details of the replay opponent.
     *
     * @return object
     */
    public function getOpponent()
    {
        return (object) [
            'type' => $this->header['opponentType'],
            'name' => isset(self::OPPONENTS[$this->header['opponentType']])
                ? self::OPPONENTS[$this->header['opponentType']]
                : 'Unknown',
        ];
    }

    /**
     * Return an object with information about the opponent's car.
     *
     * @return object
     */
    public function getOpponentCar()
    {
        return (object) [
            'name'         => $this->header['opponentCar'],
            'color'        => $this->header['opponentColor'],
            'transmission' => $this->header['opponentTransmission'],
        ];
    }

    /**
     * Return the name of the track stored in the replay.
     *
     * @return string
     */
    public function getTrackName()
    {
        return $this->header['trackName'];
    }

    /**
     * Return the SHA1 hash of the replay track data.
     *
     * @return string
     */
    public function getTrackHash()
    {
        return sha1($this->trackData);
    }

    /**
     * Return a Track object for the track stored in the replay.
     *
     * @return Track
     */
    public function getTrack()
    {
        if (!$this->trackInstance) {
            $this->trackInstance = TrackLoader::fromData($this->trackData);
        }

        return $this->trackInstance;
    }

    /**
     * Return the keyboard events data from the replay.
     *
     * @return object
     */
    public function getKeyboardEvents()
    {
        return (object) $this->recording;
    }

    /**
     * Return the driven time for the replay. For apparently complete replays
     * this is the recorded time minus one second.
     *
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Return the recorded time for the replay.
     *
     * @return float
     */
    public function getRecordedTime()
    {
        return $this->recordedTime;
    }

    /**
     * Return true if the replay appears to be incomplete
     *
     * @return boolean
     */
    public function isIncomplete()
    {
        return $this->isIncomplete;
    }

    /**
     * Return true if any key was pressed during the last second of the replay.
     *
     * @param string $events    The keyboard data for the replay
     * @param int $granularity  The number of events per second in the replay
     * @return boolean
     */
    private function hasLastSecondActivity($events, $granularity)
    {
        // extract the events for the last second of the replay
        $lastSecond = substr($events, 0 - $granularity);

        // after removing all zeros, we should be left with an empty string;
        // otherwise, the replay has probably been incomplete manually or
        // by the player.
        return strlen(trim($lastSecond, "\0")) > 0;
    }
}
