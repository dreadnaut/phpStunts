<?php
use PhpStunts\Track;

describe('load', function() {
    it('throws if it cannot read the track file', function() {
        $filename = 'this-is-not-a-track';
        expect(fn() => Track::load($filename))
            ->toThrow("Cannot read track file: {$filename}");
    });

    it('returns a Track object', function() {
        $filename = sample(TRACK_DEFAULT);
        expect(Track::load($filename))
            ->toBeInstanceOf(Track::class);
    });

    it('uses the capitalized filename as track name', function() {
        $filename = sample(TRACK_DEFAULT);
        $track = Track::load($filename);
        $name = strtoupper(basename($filename, '.trk'));
        expect($track->name)->toBe($name);
    });

    it('truncates the filename to 8 characters', function() {
        $filename = sample(TRACK_WITH_APPENDED_DATA);
        $track = Track::load($filename);
        $name = substr(strtoupper(basename($filename, '.trk')), 0, 8);
        expect($track->name)->toBe($name);
    });
});

describe('decode', function() {
    it('throws if the track is too small', function() {
        $data = 'this-is-too-short';
        $size = strlen($data);
        expect(fn() => Track::decode($data))
            ->toThrow("The track data is too small: {$size} < " . Track::BASE_SIZE);
    });

    it('returns a Track object', function() {
        $data = sampleData(TRACK_DEFAULT);
        expect(Track::decode($data))->toBeInstanceOf(Track::class);
    });

    it('extracts the track details', function() {
        $data = sampleData(TRACK_DEFAULT);
        $track = Track::decode($data);
        expect($track->layout)->toEqual(substr($data, 0, 900));
        expect($track->terrain)->toEqual(substr($data, 901, 900));
        expect($track->horizon)->toEqual(ord($data[900]));
        expect($track->reserved)->toEqual(ord($data[1801]));
        expect($track->appended)->toBeEmpty();
    });

    it('extracts appended data if present', function() {
        $data = sampleData(TRACK_WITH_APPENDED_DATA);
        $track = Track::decode($data);
        expect($track->appended)->toStartWith('smdf');
    });

    it('does not set a track name', function() {
        $data = sampleData(TRACK_DEFAULT);
        $track = Track::decode($data);
        expect($track->name)->toBeEmpty();
    });
});

describe('empty', function() {
    beforeEach(function() {
        $this->track = Track::empty();
    });

    it('returns a Track object', function() {
        expect($this->track)->toBeInstanceOf(Track::class);
    });

    it('returns a track with no elements or terrain features', function() {
        expect($this->track->layout)->toEqual(str_repeat("\x00", 900));
    });

    it('returns a track with no hills or water', function() {
        expect($this->track->terrain)->toEqual(str_repeat("\x00", 900));
    });

    it('returns a track with the desert horizon', function() {
        expect($this->track->horizon)->toBe(Track::HORIZON_DESERT);
    });

    it('returns a track with zero as the reserved byte', function() {
        expect($this->track->reserved)->toBe(0);
    });

    it('returns a track without appended data', function() {
        expect($this->track->appended)->toBeEmpty();
    });

    it('does not set a track name', function() {
        expect($this->track->name)->toBeEmpty();
    });
});

describe('normalize', function() {
    beforeEach(function() {
        $this->original = Track::load(sample(TRACK_WITH_APPENDED_DATA));
        $this->normalized = $this->original->normalize();
    });

    it('creates a new Track instance', function() {
        expect($this->normalized)->not->toBe($this->original);
    });

    it('leaves the track as is', function() {
        expect($this->normalized->layout)->toEqual($this->original->layout);
        expect($this->normalized->terrain)->toEqual($this->original->terrain);
        expect($this->normalized->horizon)->toEqual($this->original->horizon);
    });

    it('zeroes the reserved byte', function() {
        expect($this->original->reserved)->not->toBe(0);
        expect($this->normalized->reserved)->toBe(0);
    });

    it('removes any appended data', function() {
        expect($this->original->appended)->not->toBeEmpty();
        expect($this->normalized->appended)->toBeEmpty();
    });
});

describe('truncate', function() {
    beforeEach(function() {
        $this->original = Track::load(sample(TRACK_WITH_APPENDED_DATA));
        $this->truncated = $this->original->truncate();
    });

    it('creates a new Track instance', function() {
        expect($this->truncated)->not->toBe($this->original);
    });

    it('leaves the track as is', function() {
        expect($this->truncated->layout)->toEqual($this->original->layout);
        expect($this->truncated->terrain)->toEqual($this->original->terrain);
        expect($this->truncated->horizon)->toEqual($this->original->horizon);
    });

    it('leaves the reserved byte as is', function() {
        expect($this->truncated->reserved)->toEqual($this->original->reserved);
    });

    it('removes any appended data', function() {
        expect($this->original->appended)->not->toBeEmpty();
        expect($this->truncated->appended)->toBeEmpty();
    });
});

describe('hash', function() {
    it('returns the sha1 hash of the encoded data', function() {
        $data = sampleData(TRACK_DEFAULT);
        $track = Track::decode($data);
        expect($track->hash())->toEqual(sha1($data));
    });
});

describe('encode', function() {
    it('returns the contents of a valid track file', function() {
        $data = sampleData(TRACK_DEFAULT);
        $track = Track::decode($data);
        expect($track->encode())->toEqual($data);
    });
});

describe('save', function() {
    $sample = sample(SAVE_TARGET);
    $cleanup = fn() => file_exists($sample) and unlink($sample);
    beforeEach($cleanup);
    afterEach($cleanup);

    beforeEach(function() {
        $this->trackData = sampleData(TRACK_WITH_APPENDED_DATA);
        $this->track = Track::decode($this->trackData);
    });

    it('saves the encoded data to the specified file', function() {
        $this->track->save(sample(SAVE_TARGET));
        $savedData = sampleData(SAVE_TARGET);
        expect($savedData)->toEqual($this->trackData);
    });

    it('returns true if it can save the file', function() {
        $success = $this->track->save(sample(SAVE_TARGET));
        expect($success)->toBeTrue();
    });

    it('returns false if it cannot save the file', function() {
        $success = $this->track->save('');
        expect($success)->toBeFalse();
    });
});
