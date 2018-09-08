<?php

namespace phpStunts;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class TrackLoaderTest extends TestCase
{

    const TRACK_STANDARD = 'tests/samples/default.trk';
    const TRACK_WITH_TRAILING_DATA = 'tests/samples/default-extra.trk';
    const TRACK_OVERSIZED = 'tests/samples/default-oversized.trk';

    /**
     * Prepare a string containing the data for a fake track.
     *
     * By default, the track follows the standard format and has no trailing
     * data.
     */
    private function prepareSampleData(
        $layoutChar = 'A',
        $terrainChar = 'B',
        $reserved = Track::RESERVED_DEFAULT,
        $extra = '',
        $horizon = Track::HORIZON_CITY
    ) {
        $layout = str_repeat($layoutChar, Track::SIZE_LAYOUT);
        $terrain = str_repeat($terrainChar, Track::SIZE_TERRAIN);
        return $layout . chr($horizon) . $terrain . chr($reserved) . $extra;
    }

    /**
     * The method should return a Track object which stores
     * all the data passed as parameter.
     */
    public function testFromData()
    {
        $trackData = $this->prepareSampleData('A', 'B', 255, 'extra-data-at-the-end');

        $track = TrackLoader::fromData($trackData);

        $this->assertInstanceOf(Track::class, $track);
        $this->assertEquals($trackData, $track->getOriginalData());
    }

    /**
     * When a file is missing, the method should throw an exception.
     */
    public function testFromFileWithInvalidFilename()
    {
        $this->expectException(\InvalidArgumentException::class);

        $track = TrackLoader::fromFile('this-is-not-a-file');
    }

    /**
     * When a file is too large, the method should throw an exception.
     */
    public function testFromFileWithOversizedFilename()
    {
        $this->expectException(\LengthException::class);

        $track = TrackLoader::fromFile(self::TRACK_OVERSIZED);
    }

    /**
     * When the method loads a standard track, the canonical data should
     * match the loaded data. No extra data should be present.
     */
    public function testFromFile()
    {
        $trackData = file_get_contents(self::TRACK_STANDARD);

        $track = TrackLoader::fromFile(self::TRACK_STANDARD);

        $this->assertInstanceOf(Track::class, $track);
        $this->assertEquals($trackData, $track->getData());
        $this->assertEquals($trackData, $track->getOriginalData());
        $this->assertEmpty($track->getExtra());
    }

    /**
     * When the method loads a non-standard track, the trailing data should
     * be loaded in the extra property.
     */
    public function testFromFileWithTrailingData()
    {
        $track = TrackLoader::fromFile(self::TRACK_WITH_TRAILING_DATA);

        $this->assertInstanceOf(Track::class, $track);
        $this->assertNotEmpty($track->getExtra());
    }
}
