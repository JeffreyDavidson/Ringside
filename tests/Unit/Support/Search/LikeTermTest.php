<?php

declare(strict_types=1);

use App\Support\Search\LikeTerm;

test('wraps the trimmed term in wildcards', function (string $term, string $pattern): void {
    // Arrange
    $like = LikeTerm::from($term);

    // Act
    $result = $like->contains();

    // Assert
    expect($result)->toBe($pattern);
})->with([
    'plain word' => ['Rock', '%Rock%'],
    'surrounding spaces' => ['  The Rock  ', '%The Rock%'],
    'inner spaces kept' => ['The  Rock', '%The  Rock%'],
    'surrounding tabs and newlines' => ["\t\nRock\r\n", '%Rock%'],
    'multibyte letters' => ['Renée Sú', '%Renée Sú%'],
]);

test('drops the LIKE wildcards and the backslash instead of escaping them', function (string $term, string $pattern): void {
    // Arrange
    $like = LikeTerm::from($term);

    // Act
    $result = $like->contains();

    // Assert
    expect($result)->toBe($pattern);
})->with([
    'percent' => ['100%', '%100%'],
    'underscore' => ['a_b', '%ab%'],
    'backslash' => ['a\\b', '%ab%'],
    'all three with spaces around' => [' %_\\ x ', '%x%'],
    'wildcards between words keep the spaces' => ['a % b', '%a  b%'],
]);

test('is empty when nothing but wildcards and whitespace is left', function (string $term, bool $empty): void {
    // Arrange
    $like = LikeTerm::from($term);

    // Act
    $result = $like->isEmpty();

    // Assert
    expect($result)->toBe($empty);
})->with([
    'empty string' => ['', true],
    'spaces' => ['   ', true],
    'only wildcards' => ['%_\\', true],
    'wildcards and spaces' => [' % _ ', true],
    'a letter' => ['a', false],
    'zero' => ['0', false],
]);

test('cuts the needle to the given length after trimming and cleaning', function (string $term, int $length, string $pattern): void {
    // Arrange
    $like = LikeTerm::from($term, $length);

    // Act
    $result = $like->contains();

    // Assert
    expect($result)->toBe($pattern);
})->with([
    'longer than the limit' => ['abcdef', 3, '%abc%'],
    'exactly the limit' => ['abc', 3, '%abc%'],
    'shorter than the limit' => ['ab', 3, '%ab%'],
    'wildcards do not count' => ['a%b%c%d', 3, '%abc%'],
    'leading spaces do not count' => ['   abcdef', 3, '%abc%'],
    'counts characters, not bytes' => ['ééééé', 2, '%éé%'],
    'a cut can leave a trailing space' => ['ab cd', 3, '%ab %'],
]);

test('does not cut the needle when no length is given', function (): void {
    // Arrange
    $term = str_repeat('a', 500);

    // Act
    $like = LikeTerm::from($term);

    // Assert
    expect($like->contains())->toBe("%{$term}%");
});
