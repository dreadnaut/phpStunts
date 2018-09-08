<?php

namespace phpStunts;

use PHPUnit\Framework\TestCase;

class ReplayTest extends TestCase
{

    /**
     * Various sample files used in the tests below.
     */
    const REPLAY_SAMPLE = 'tests/samples/vs-skid.rpl';
    const REPLAY_SAMPLE_TIME = 85;
    const REPLAY_INCOMPLETE = 'tests/samples/default-incomplete.rpl';
    const REPLAY_NO_OPPONENT = 'tests/samples/vancouvr-1.1.rpl';
    const REPLAY_VERSION_10 = 'tests/samples/funhills-1.0.rpl';
    const REPLAY_VERSION_11 = 'tests/samples/vancouvr-1.1.rpl';
    const TRACK_FOR_REPLAY_VERSION_11 = 'tests/samples/vancouvr.trk';

    /**
     * The sample replay used for most tests.
     */
    protected $replay;

    /**
     * Called before each test.
     */
    protected function setUp()
    {
        $this->replay = (new ReplayLoader)->fromFile(self::REPLAY_SAMPLE);
    }

    /**
     * Return a slice of the sample replay file.
     */
    private function sliceSample($start, $length = null)
    {
        $length = $length ?: filesize(self::REPLAY_SAMPLE) - $start;

        return substr(file_get_contents(self::REPLAY_SAMPLE), $start, $length);
    }

    /**
     * The method should correctly identify version 1.0.
     */
    public function testGetVersionWith10()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_VERSION_10);
        $this->assertEquals(
            Replay::VERSION_10,
            $replay->getVersion()
        );
    }

    /**
     * The method should correctly identify version 1.1.
     */
    public function testIsVersion11()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_VERSION_11);
        $this->assertEquals(
            Replay::VERSION_11,
            $replay->getVersion()
        );
    }

    /**
     * The method should return the correct car information.
     */
    public function testGetCar()
    {
        $expectedCar = (object) [
            'name' => 'P962',
            'color' => 3,
            'transmission' => 0,
        ];

        $car = $this->replay->getCar();

        $this->assertEquals($expectedCar, $car);
    }

    /**
     * The method should return true for replays that have an opponent.
     */
    public function testHasOpponent()
    {
        $this->assertTrue($this->replay->hasOpponent());
    }

    /**
     * The method should return false for replays that don't have an opponent.
     */
    public function testHasOpponentWithoutOpponent()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_NO_OPPONENT);
        $this->assertFalse($replay->hasOpponent());
    }

    /**
     * The method should return type and name for the replay opponent.
     */
    public function testGetOpponent()
    {
        $expectedOpponent = (object) [
            'type' => Replay::OPPONENT_SKID,
            'name' => Replay::OPPONENTS[Replay::OPPONENT_SKID],
        ];

        $opponent = $this->replay->getOpponent();

        $this->assertEquals($expectedOpponent, $opponent);
    }

    /**
     * If no opponent was set, the method should return 'None'.
     */
    public function testGetOpponentWithoutOpponent()
    {
        $expectedOpponent = (object) [
            'type' => Replay::OPPONENT_NONE,
            'name' => Replay::OPPONENTS[Replay::OPPONENT_NONE],
        ];

        $replay = (new ReplayLoader)->fromFile(self::REPLAY_NO_OPPONENT);
        $opponent = $replay->getOpponent();

        $this->assertEquals($expectedOpponent, $opponent);
    }

    /**
     * The method should return the details of the opponent's car.
     */
    public function testGetOpponentCar()
    {
        $expectedCar = (object) [
            'name' => 'PMIN',
            'color' => 2,
            'transmission' => 1,
        ];

        $car = $this->replay->getOpponentCar();

        $this->assertEquals($expectedCar, $car);
    }

    /**
     * If no opponent was set, the record should contain empty values.
     */
    public function testGetOpponentCarWithoutOpponent()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_NO_OPPONENT);

        $expectedCar = (object) [
            'name' => Replay::NO_OPPONENT_CAR,
            'color' => 0,
            'transmission' => 0,
        ];

        $car = $replay->getOpponentCar();

        $this->assertEquals($expectedCar, $car);
    }

    /**
     * The method should return a record with keyboard data.
     */
    public function testGetKeyboardEvents()
    {
        $expectedRecording = (object) [
            'granularity' => 20,
            'keyboardEvents' => $this->sliceSample(0x1C + 0x70A),
            'length' => 1700,
        ];

        $recording = $this->replay->getKeyboardEvents();

        $this->assertEquals($expectedRecording, $recording);
    }

    /**
     * The method should return the length of the keyboard data.
     */
    public function testGetRecordedTime()
    {
        $time = $this->replay->getRecordedTime();

        $this->assertEquals(self::REPLAY_SAMPLE_TIME, $time);
    }

    /**
     * For complete replays, the method should remove one second from the
     * recorded time.
     */
    public function testGetTime()
    {
        $time = $this->replay->getTime();

        $this->assertEquals(self::REPLAY_SAMPLE_TIME - 1, $time);
    }

    /**
     * For incomplete replays, the method should return the recorded time.
     */
    public function testGetTimeWithIncomplete()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_INCOMPLETE);

        $this->assertEquals(
            $replay->getRecordedTime(),
            $replay->getTime()
        );
    }

    /**
     * The method should return a Track instance for the replay track.
     */
    public function testGetTrack()
    {
        $track = $this->replay->getTrack();

        $this->assertInstanceOf(Track::class, $track);
    }

    /**
     * The method should return the SHA1 hash of the track data.
     */
    public function testGetTrackHash()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_VERSION_11);

        $this->assertEquals(
            sha1_file(self::TRACK_FOR_REPLAY_VERSION_11),
            $replay->getTrackHash()
        );
    }

    /**
     * The method should return the track name stored in the replay.
     */
    public function testGetTrackName()
    {
        $this->assertEquals('DEFAULT', $this->replay->getTrackName());
    }

    /**
     * The method should return false for replay that cannot be considered
     * incomplete.
     */
    public function testIsIncomplete()
    {
        $this->assertFalse($this->replay->isIncomplete());
    }

    /**
     * The method should return true for replays that seem to be incomplete.
     */
    public function testIsIncompleteWithIncomplete()
    {
        $replay = (new ReplayLoader)->fromFile(self::REPLAY_INCOMPLETE);
        $this->assertTrue($replay->isIncomplete());
    }
}
