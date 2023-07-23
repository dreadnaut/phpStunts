<?php
use PhpStunts\Replay;
use PhpStunts\ReplayReader;

describe('load', function() {
    it('throws if the file does not exist', function() {
        $filename = 'this-is-not-a-file';
        expect(fn() => ReplayReader::load($filename))
            ->toThrow("Cannot read replay file: {$filename}");
    });

    it('returns a Replay', function() {
        $replay = ReplayReader::load(sample(REPLAY_DEFAULT));
        expect($replay)->toBeInstanceOf(Replay::class);
    });
});

describe('decode', function() {
    it('returns a Replay', function() {
        $replay = ReplayReader::decode(sampleData(REPLAY_DEFAULT));
        expect($replay)->toBeInstanceOf(Replay::class);
    });
});

describe('extractHeader', function() {
    it('throws if the file is too small', function() {
        $min = ReplayReader::MIN_SIZE;
        $tooLittle = "this is shorter than {$min}";
        $length = strlen($tooLittle);
        expect(fn() => ReplayReader::extractHeader($tooLittle))
            ->toThrow("The replay data is too small: {$length} < {$min}");
    });

    it('throws if the file is too large', function() {
        $max = ReplayReader::MAX_SIZE;
        $tooMuch = str_repeat('x', $max + 10);
        $length = strlen($tooMuch);
        expect(fn() => ReplayReader::extractHeader($tooMuch))
            ->toThrow("The replay data is too large: {$length} > {$max}");
    });

    it('throws if it cannot recognise the format', function() {
        $data = str_repeat('x', ReplayReader::MIN_SIZE);
        expect(fn() => ReplayReader::extractHeader($data))
            ->toThrow('The replay data is invalid');
    });

    it('reads the header of replays version 1.0', function() {
        $data = sampleData(REPLAY_10);
        $header = ReplayReader::extractHeader($data);
        expect($header->version)->toBe(Replay::VERSION_10);
    });

    it('reads the header of replays version 1.1', function() {
        $data = sampleData(REPLAY_11_SLOW);
        $header = ReplayReader::extractHeader($data);
        expect($header->version)->toBe(Replay::VERSION_11);
    });

    it('returns the replay details', function() {
        $data = sampleData('vs-skid.rpl');
        $header = ReplayReader::extractHeader($data);
        expect($header->playerCar)->toBe('P962');
        expect($header->playerColor)->toBe(3);
        expect($header->playerTransmission)->toBe(0);
        expect($header->opponentType)->toBe(6);
        expect($header->opponentCar)->toBe('PMIN');
        expect($header->opponentColor)->toBe(2);
        expect($header->opponentTransmission)->toBe(1);
        expect($header->trackName)->toBe('DEFAULT');
        expect($header->version)->toBe(Replay::VERSION_11);
    });
});

describe('extractTrack', function() {
    it('reads track data from replays version 1.0', function () {
        $data = sampleData(REPLAY_10);
        $expectedTrackData = sampleData(TRACK_FOR_REPLAY_10);

        $trackData = ReplayReader::extractTrack($data, Replay::VERSION_10);

        expect($trackData)->toBe($expectedTrackData);
    });

    it('reads track data from replays version 1.1', function () {
        $data = sampleData(REPLAY_11_FAST);
        $expectedTrackData = sampleData(TRACK_FOR_REPLAY_11);

        $trackData = ReplayReader::extractTrack($data, Replay::VERSION_11);

        expect($trackData)->toBe($expectedTrackData);
    });
});

describe('extractRecording', function() {
    it('reads recording data from replays version 1.0', function () {
        $data = sampleData(REPLAY_10);
        $recording = ReplayReader::extractRecording($data, Replay::VERSION_10);

        expect($recording->granularity)->toBe(20);
        expect($recording->keyboardEvents)->toEqual(substr($data, 0x18 + 0x70a));
        expect($recording->time)->toBe(4977 * 100 / 20);
    });

    it('reads recording data from replays version 1.1', function () {
        $data = sampleData(REPLAY_11_FAST);
        $recording = ReplayReader::extractRecording($data, Replay::VERSION_11);

        expect($recording->granularity)->toBe(20);
        expect($recording->keyboardEvents)->toEqual(substr($data, 0x1a + 0x70a));
        expect($recording->time)->toBe(1661 * 100 / 20);
    });

    it('calculate the correct time with slow simulation speed', function () {
        $data = sampleData(REPLAY_11_SLOW);
        $recording = ReplayReader::extractRecording($data, Replay::VERSION_11);

        expect($recording->granularity)->toBe(10);
        expect($recording->keyboardEvents)->toEqual(substr($data, 0x1a + 0x70a));
        expect($recording->time)->toBe(954 * 100 / 10);
    });

    it('throws if the declared length is different from the actual one', function() {
        $data = sampleData(REPLAY_INVALID_LENGTH);
        expect(fn() => ReplayReader::extractRecording($data, Replay::VERSION_11))
            ->toThrow("Replay length should be 479, actually 447");
    });
});
