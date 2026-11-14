<?php

use Steddle\Seal\Fingerprint;
use Steddle\Seal\Refused;

/**
 * @return array{vectors: list<array{name: string, in: string, accept: bool, prints?: string, why?: string}>, equal: list<list<string>>}
 */
function fingerprintVectors(): array
{
    return json_decode(file_get_contents(dirname(__DIR__).'/vectors/fingerprint.json'), true, flags: JSON_THROW_ON_ERROR);
}

test('the vector file holds 13 inputs and one group of equal values', function () {
    expect(fingerprintVectors()['vectors'])->toHaveCount(13)
        ->and(fingerprintVectors()['equal'])->toHaveCount(1);
});

test('each input reads and prints as its vector states', function (array $vector) {
    if ($vector['accept']) {
        expect((string) Fingerprint::read($vector['in']))->toBe($vector['prints']);

        return;
    }

    expect(fn () => Fingerprint::read($vector['in']))
        ->toThrow(fn (Refused $refused) => expect($refused->why)->toBe($vector['why']));
})->with(function () {
    foreach (fingerprintVectors()['vectors'] as $vector) {
        yield $vector['name'] => [$vector];
    }
});

test('every spelling in a group of equal values reads as the same fingerprint', function (array $group) {
    $first = Fingerprint::read($group[0]);

    foreach ($group as $written) {
        expect(Fingerprint::read($written)->equals($first))->toBeTrue()
            ->and((string) Fingerprint::read($written))->toBe((string) $first);
    }
})->with(fn () => array_map(fn (array $group) => [$group], fingerprintVectors()['equal']));

test('the fingerprint of zero bytes is the example the vectors read', function () {
    expect(Fingerprint::of('')->equals(Fingerprint::read(fingerprintVectors()['vectors'][0]['in'])))->toBeTrue()
        ->and(Fingerprint::of('')->equals(Fingerprint::of(' ')))->toBeFalse();
});
