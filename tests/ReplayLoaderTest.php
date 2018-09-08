<?php

namespace phpStunts;

use PHPUnit\Framework\TestCase;

class ReplayLoaderTest extends TestCase
{

    const REPLAY_PATH = 'tests/samples/';

    /**
     * A replay of a race with an opponent, with different cars and
     * transmission settings.
     */
    const REPLAY_DEFAULT = 'vs-skid.rpl';
    const TRACK_DEFAULT = 'default.trk';

    /**
     * Replays and matching tracks for both Stunts versions.
     */
    const REPLAY_10 = 'funhills-1.0.rpl';
    const TRACK_FOR_REPLAY_10 = 'funhills.trk';
    const REPLAY_11 = 'vancouvr-1.1.rpl';
    const TRACK_FOR_REPLAY_11 = 'vancouvr.trk';

    /**
     * When a file is missing, the function should throw an exception.
     */
    public function testFromFileWithInvalidFilename()
    {
        $this->expectException(\InvalidArgumentException::class);

        ReplayLoader::fromFile('this-is-not-a-file');
    }

    /**
     * When a file is too small, the function should throw an exception.
     */
    public function testFromFileWithFileTooSmall()
    {
        $this->expectException(\InvalidArgumentException::class);

        ReplayLoader::fromFile(self::REPLAY_PATH . self::TRACK_DEFAULT);
    }

    /**
     * When the replay file can be loaded, the method should return a
     * replay instance.
     */
    public function testFromFile()
    {
        $replay = ReplayLoader::fromFile(self::REPLAY_PATH . self::REPLAY_DEFAULT);

        $this->assertInstanceOf(Replay::class, $replay);
    }

    /**
     *
     */
    public function testFromData()
    {
        $replayData = $this->load(self::REPLAY_DEFAULT);

        $replay = ReplayLoader::fromData($replayData);

        $this->assertInstanceOf(Replay::class, $replay);
    }

    /**
     * If the replay was created with Stunts 1.0, the method should return
     * VERSION_10.
     */
    public function testDetectVersion()
    {
        $data = $this->load(self::REPLAY_10);

        $version = ReplayLoader::detectVersion($data);

        $this->assertEquals(Replay::VERSION_10, $version);
    }

    /**
     * If the replay was created with Stunts 1.0, the method should return
     * VERSION_11.
     */
    public function testDetectVersionWithVersion11()
    {
        $data = $this->load(self::REPLAY_11);

        $version = ReplayLoader::detectVersion($data);

        $this->assertEquals(Replay::VERSION_11, $version);
    }

    /**
     * If the replay was created with Stunts 1.0, the method should return
     * false.
     */
    public function testDetectVersionWithInvalidVersion()
    {
        $data = 'this is not valid replay data';

        $version = ReplayLoader::detectVersion($data);

        $this->assertFalse($version);
    }

    /**
     * The method should throw an exception when the data is too small to
     * contain a replay header.
     */
    public function testUnpackHeaderWithNotEnoughData()
    {
        $this->expectException(\PHPUnit\Framework\Error\Error::class);

        $data = 'less-than-22-bytes';

        $header = ReplayLoader::unpackHeader($data);
    }

    /**
     * The method should return an array with the correct replay header fields.
     */
    public function testUnpackHeader()
    {
        $data = $this->load('vs-skid.rpl');
        $expectedHeader = [
            'playerCar'            => 'P962',
            'playerColor'          => 3,
            'playerTransmission'   => 0,
            'opponentType'         => 6,
            'opponentCar'          => 'PMIN',
            'opponentColor'        => 2,
            'opponentTransmission' => 1,
            'trackName'            => 'DEFAULT',
        ];


        $header = ReplayLoader::unpackHeader($data);

        $this->assertEquals($expectedHeader, $header);
    }

    /**
     * The method should return the slice of replay data that represents the
     * driven track.
     */
    public function testExtractTrackWithVersion10()
    {
        $data = $this->load(self::REPLAY_10);
        $expectedTrackData = $this->load(self::TRACK_FOR_REPLAY_10);

        $trackData = ReplayLoader::extractTrack($data, Replay::VERSION_10);

        $this->assertEquals($expectedTrackData, $trackData);
    }

    /**
     * The method should return the slice of replay data that represents the
     * driven track.
     */
    public function testExtractTrackWithVersion11()
    {
        $data = $this->load(self::REPLAY_11);
        $expectedTrackData = $this->load(self::TRACK_FOR_REPLAY_11);

        $trackData = ReplayLoader::extractTrack($data, Replay::VERSION_11);

        $this->assertEquals($expectedTrackData, $trackData);
    }

    /**
     * The method should return an associative array with length, granularity
     * and keyboard events for the replay.
     */
    public function testExtractRecordingWithVersion10()
    {
        $data = $this->load(self::REPLAY_10);
        $expectedRecording = [
            'granularity' => 20,
            'length' => 4977,
            'keyboardEvents' => substr($data, 26 + 1802),
        ];

        $recording = ReplayLoader::extractRecording($data, Replay::VERSION_10);

        $this->assertEquals($expectedRecording, $recording);
    }

    /**
     * The method should return an associative array with length, granularity
     * and keyboard events for the replay.
     */
    public function testExtractRecordingWithVersion11()
    {
        $data = $this->load(self::REPLAY_11);
        $expectedRecording = [
            'granularity' => 20,
            'length' => 958,
            'keyboardEvents' => substr($data, 28 + 1802),
        ];

        $recording = ReplayLoader::extractRecording($data, Replay::VERSION_11);

        $this->assertEquals($expectedRecording, $recording);
    }

    /**
     * Utility method reading a file from the test sample directory.
     */
    private function load($filename)
    {
        return file_get_contents(self::REPLAY_PATH . $filename);
    }
}
