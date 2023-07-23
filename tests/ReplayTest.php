<?php
use PhpStunts\Replay;
use PhpStunts\Track;

describe('load', function() {
    it('returns a Replay', function() {
        $replay = Replay::load(sample(REPLAY_DEFAULT));
        expect($replay)->toBeInstanceOf(Replay::class);
    });
});

describe('decode', function() {
    it('returns a Replay', function() {
        $replay = Replay::decode(sampleData(REPLAY_DEFAULT));
        expect($replay)->toBeInstanceOf(Replay::class);
    });
});

describe('attributes', function() {
    describe('car', function() {
        it('describes the player\'s car', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            expect($replay->car->name)->toBe('P962');
            expect($replay->car->color)->toBe(3);
            expect($replay->car->transmission)->toBe(0);
        });
    });

    describe('opponent', function() {
        it('contains the opponent type', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            expect($replay->opponent)->toBe(6);
        });
    });

    describe('opponentCar', function() {
        it('describe the opponent\'s car', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            expect($replay->opponentCar->name)->toBe('PMIN');
            expect($replay->opponentCar->color)->toBe(2);
            expect($replay->opponentCar->transmission)->toBe(1);
        });
    });

    describe('track', function() {
        it('returns a Track object', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            expect($replay->track)->toBeInstanceOf(Track::class);
        });

        it('contains the replay track', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            expect($replay->track->horizon)->toBe(1);
        });

        it('extracts the track name from the replay', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            expect($replay->track->name)->toBe('DEFAULT');
        });
    });

    describe('time', function() {
        it('returns the recording time for incomplete replays', function() {
            $replay = Replay::load(sample(REPLAY_INCOMPLETE));
            expect($replay->time)->toEqual($replay->recording->time);
        });

        it('returns the lap time for replays that seem complete', function() {
            $replay = Replay::load(sample(REPLAY_DEFAULT));
            $lapTime = $replay->recording->time - 100;
            expect($replay->time)->toEqual($lapTime);
        });

        it('works with "dropped frame" replays', function() {
            $replay = Replay::load(sample(REPLAY_DROPPED_FRAME));
            $lapTime = $replay->recording->time - $replay->recording->granularity;
            expect($replay->time)->toEqual($lapTime);
        })->skip();
    });

    describe('version', function() {
        it('returns 1.0 for 1.0 replays', function() {
            $replay = Replay::load(sample(REPLAY_10));
            expect($replay->version)->toBe('1.0');
        });

        it('returns 1.1 for 1.1 replays', function() {
            $replay = Replay::load(sample(REPLAY_11_FAST));
            expect($replay->version)->toBe('1.1');
        });
    });
});
