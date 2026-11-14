<?php

use Steddle\Seal\Fingerprint;
use Steddle\Seal\Reference;
use Steddle\Seal\Refused;
use Steddle\Seal\Statement;

/**
 * @return array{statements: list<array{name: string, statement: array<string, mixed>, bytes: string, fingerprint: string, references: list<array{at: string, over: string}>}>, refused: list<array{name: string, statement: mixed, why: list<string>}>}
 */
function statementVectors(): array
{
    return json_decode(file_get_contents(dirname(__DIR__).'/vectors/statement.json'), true, flags: JSON_THROW_ON_ERROR);
}

test('the vector file holds 3 statements and 16 refusals', function () {
    expect(statementVectors()['statements'])->toHaveCount(3)
        ->and(statementVectors()['refused'])->toHaveCount(16);
});

test('each statement writes its bytes, its fingerprint and its references', function (array $vector) {
    $statement = Statement::of($vector['statement']);

    expect($statement->bytes())->toBe($vector['bytes'])
        ->and((string) $statement->fingerprint())->toBe($vector['fingerprint'])
        ->and($statement->form())->toBe($vector['statement']['form'])
        ->and(array_map(fn (Reference $reference, string $at): array => ['at' => $at, 'over' => $reference->over], $statement->references(), array_keys($statement->references())))
        ->toBe($vector['references']);
})->with(function () {
    foreach (statementVectors()['statements'] as $vector) {
        yield $vector['name'] => [$vector];
    }
});

test('a statement read from its own bytes is the same statement', function (array $vector) {
    $statement = Statement::read($vector['bytes']);

    expect($statement->bytes())->toBe($vector['bytes'])
        ->and($statement->fingerprint()->equals(Fingerprint::read($vector['fingerprint'])))->toBeTrue();
})->with(function () {
    foreach (statementVectors()['statements'] as $vector) {
        yield $vector['name'] => [$vector];
    }
});

test('each refused statement names its problems and is refused for the first', function (mixed $statement, array $why) {
    expect(Statement::problems($statement))->toBe($why)
        ->and(fn () => Statement::of($statement))->toThrow(fn (Refused $refused) => expect($refused->why)->toBe($why[0]));
})->with(function () {
    foreach (statementVectors()['refused'] as $vector) {
        yield $vector['name'] => [$vector['statement'], $vector['why']];
    }
});

test('a reference holds a fingerprint and what it was taken over', function () {
    $reference = Reference::to(Fingerprint::of('a file'), 'bytes');

    expect($reference->toArray())->toBe(['fingerprint' => (string) Fingerprint::of('a file'), 'over' => 'bytes'])
        ->and(Statement::of(['form' => 'example.file/1', 'file' => $reference->toArray()])->references())->toEqual(['/file' => $reference]);
});

test('a reference over something that is no form is refused', function () {
    expect(fn () => Reference::to(Fingerprint::of('a file'), 'json_encode'))
        ->toThrow(fn (Refused $refused) => expect($refused->why)->toBe('over_pattern'));
});
