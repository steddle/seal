<?php

use Steddle\Seal\Canonical;
use Steddle\Seal\Fingerprint;
use Steddle\Seal\Json;
use Steddle\Seal\Refused;

/**
 * @return array{numbers: list<array{ieee: string, prints: string}>, values: list<array{name: string, text: string, prints: string}>, refused: list<array{name: string, text: string, why: string}>}
 */
function canonicalVectors(): array
{
    return json_decode(file_get_contents(dirname(__DIR__).'/vectors/canonical.json'), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<string, string> each VCRS record file by its path under vectors/
 */
function vcrsRecords(): array
{
    $files = [];

    foreach (glob(__DIR__.'/vcrs/vectors/*/*.json') as $path) {
        $files[basename(dirname($path)).'/'.basename($path, '.json')] = $path;
    }

    return $files;
}

test('the vector file holds 26 numbers, 7 values and 5 refusals, and the VCRS copy 138 records', function () {
    expect(canonicalVectors()['numbers'])->toHaveCount(26)
        ->and(canonicalVectors()['values'])->toHaveCount(7)
        ->and(canonicalVectors()['refused'])->toHaveCount(5)
        ->and(vcrsRecords())->toHaveCount(138);
});

test('each double prints as appendix B of RFC 8785 states', function (string $ieee, string $prints) {
    $double = unpack('E', hex2bin($ieee))[1];

    if (str_starts_with($prints, 'refused:')) {
        expect(fn () => Canonical::json($double))
            ->toThrow(fn (Refused $refused) => expect($refused->why)->toBe(substr($prints, strlen('refused:'))));

        return;
    }

    expect(Canonical::json($double))->toBe($prints);
})->with(function () {
    foreach (canonicalVectors()['numbers'] as $number) {
        yield $number['ieee'] => [$number['ieee'], $number['prints']];
    }
});

test('each text prints its canonical form', function (string $text, string $prints) {
    expect(Canonical::json(Json::decode($text)))->toBe($prints);
})->with(function () {
    foreach (canonicalVectors()['values'] as $value) {
        yield $value['name'] => [$value['text'], $value['prints']];
    }
});

test('each refused text is refused for its stated reason', function (string $text, string $why) {
    expect(fn () => Canonical::json(Json::decode($text)))
        ->toThrow(fn (Refused $refused) => expect($refused->why)->toBe($why));
})->with(function () {
    foreach (canonicalVectors()['refused'] as $refused) {
        yield $refused['name'] => [$refused['text'], $refused['why']];
    }
});

test('text that is not JSON is refused as not JSON, and NaN inside a string is text', function () {
    expect(fn () => Json::decode('[1,]'))->toThrow(fn (Refused $refused) => expect($refused->why)->toBe('not_json'))
        ->and(fn () => Json::decode('{"a":1'))->toThrow(fn (Refused $refused) => expect($refused->why)->toBe('not_json'))
        ->and(fn () => Json::decode('{"x":{"a":1,"a":2}}'))->toThrow(fn (Refused $refused) => expect($refused->why)->toBe('duplicate_name'))
        ->and(Canonical::json(Json::decode('["NaN", "-Infinity"]')))->toBe('["NaN","-Infinity"]');
});

test('each VCRS record gets its stated digest', function (string $path) {
    $file = Json::decode(file_get_contents($path));
    $record = clone $file->record;
    unset($record->digest);

    expect((string) Fingerprint::of(Canonical::json($record)))->toBe($file->expected->digest);
})->with(vcrsRecords(...));
