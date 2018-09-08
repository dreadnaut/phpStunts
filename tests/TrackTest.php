<?php

namespace phpStunts;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class TrackTest extends TestCase
{

    const TRACK_STANDARD = 'tests/samples/default.trk';
    const TRACK_WITH_TRAILING_DATA = 'tests/samples/default-extra.trk';

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
     * A new empty track should have no data set, except the horizon value.
     */
    public function testEmpty()
    {
        $horizon = Track::HORIZON_COUNTRY;

        $track = Track::empty($horizon);

        $this->assertInstanceOf(Track::class, $track);
        $this->assertEmpty($track->getExtra());
        $this->assertEquals(0, $track->getReserved());
        $this->assertEquals($horizon, $track->getHorizon());
    }

    /**
     * The canonical data of a standard track matches the file data.
     */
    public function testGetData()
    {
        $trackData = $this->prepareSampleData();

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($trackData, $track->getData());
    }

    /**
     * The canonical data of a non-standard track matches a track with the
     * same layout, terrain and horizon, but no reserved and extra data.
     */
    public function testGetDataWithTrailingData()
    {
        $trackCanonical = $this->prepareSampleData('L', 'T');
        $trackData = $this->prepareSampleData('L', 'T', 151, str_repeat('X', 100));

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($trackCanonical, $track->getData());
        $this->assertNotEquals($trackData, $track->getData());
    }

    /**
     * The original data of a standard track matches the file data.
     */
    public function testGetOriginalData()
    {
        $trackData = $this->prepareSampleData();

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($trackData, $track->getData());
    }

    /**
     * The original data of a non-standard track also matches the file data.
     */
    public function testGetOriginalDataWithTrailingData()
    {
        $trackData = $this->prepareSampleData();

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($trackData, $track->getData());
    }

    /**
     * The extra data of a standard track should be empty.
     */
    public function testGetExtra()
    {
        $trackData = $this->prepareSampleData();

        $track = TrackLoader::fromData($trackData);

        $this->assertEmpty($track->getExtra());
    }

    /**
     * The extra data of a non-standard track should include all trailing data.
     */
    public function testGetExtraWithTrailingData()
    {
        $extra = 'extra-data-here';
        $trackData = $this->prepareSampleData('A', 'B', 123, $extra);

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($extra, $track->getExtra());
    }

    /**
     * The hash of a track it's the hash of its canonical data.
     */
    public function testGetHash()
    {
        $canonicalData = $this->prepareSampleData('X', 'Y');
        $trackData = $this->prepareSampleData('X', 'Y', 151, 'some-extra-data');

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals(sha1($canonicalData), $track->getHash());
    }

    /**
     * The method should return the horizon byte from the track data.
     */
    public function testGetHorizon()
    {
        $horizon = Track::HORIZON_ALPINE;
        $trackData = $this->prepareSampleData('A', 'B', 0, '', $horizon);

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($horizon, $track->getHorizon());
    }

    /**
     * The method should return the reserved byte from the track data.
     */
    public function testGetReserved()
    {
        $reserved = 152;
        $trackData = $this->prepareSampleData('A', 'B', $reserved);

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($reserved, $track->getReserved());
    }

    /**
     * The method should return the layout part of the track data.
     */
    public function testGetLayout()
    {
        $trackData = $this->prepareSampleData('L', 'x');
        $layout = str_repeat('L', Track::SIZE_LAYOUT);

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($layout, $track->getLayout());
    }

    /**
     * The method should return the terrain part of the track data.
     */
    public function testGetTerrain()
    {
        $trackData = $this->prepareSampleData('x', 'T');
        $terrain = str_repeat('T', Track::SIZE_TERRAIN);

        $track = TrackLoader::fromData($trackData);

        $this->assertEquals($terrain, $track->getTerrain());
    }
}
