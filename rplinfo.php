<?php

require_once 'vendor/autoload.php';

$filename = $argv[1];

$replay = PhpStunts\Replay::load($filename);

?>

Track:       <?= $replay->track->name ?> (<?= $replay->track->hash() ?>)
Car:         <?= $replay->car->name ?> <?= $replay->car->transmission == 1 ? 'auto' : 'manual' ?>


Version:     <?= $replay->version ?>

Granularity: <?= $replay->recording->granularity ?>

Time:        <?= $replay->time ?> (<?= $replay->recording->time ?>)

<?php if ($replay->opponent): ?>
Opponent:    <?= $replay->opponent ?>

Car:         <?= $replay->opponentCar?->name ?> <?= $replay->opponentCar?->transmission == 1 ? 'auto' : 'manual' ?>
<?php else: ?>
No opponent
<?php endif ?>

