<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function sample(string $filename) : string
{
    return "tests/samples/{$filename}";
}

function sampleData(string $filename) : string
{
    $sample = sample($filename);
    file_exists($sample) or throw new Exception("Sample {$filename} does not exist");
    return file_get_contents($sample);
}


/**
 * A replay of a race with an opponent, with different cars and
 * transmission settings.
 */
const REPLAY_DEFAULT = 'vs-skid.rpl';
const TRACK_DEFAULT = 'default.trk';
const TRACK_WITH_APPENDED_DATA = 'default-with-appended-data.trk';
const SAVE_TARGET = 'save-target.trk';

const REPLAY_INCOMPLETE = 'default-incomplete.rpl';
const REPLAY_DROPPED_FRAME = 'dropped-frame.rpl';
const REPLAY_INVALID_LENGTH = 'default-invalid-length.rpl';

/**
 * Replays and matching tracks for both Stunts versions.
 */
const REPLAY_10 = 'funhills-1.0.rpl';
const REPLAY_11_FAST = 'vancouvr-1.1-fast.rpl';
const REPLAY_11_SLOW = 'vancouvr-1.1-slow.rpl';

const TRACK_FOR_REPLAY_10 = 'funhills.trk';
const TRACK_FOR_REPLAY_11 = 'vancouvr.trk';


