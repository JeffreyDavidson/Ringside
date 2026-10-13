<?php

declare(strict_types=1);
use App\ValueObjects\UsdAmount;

test('USD input is parsed exactly without floating point', function (): void {
    expect(UsdAmount::parse('0.10'))->toBe(10)
        ->and(UsdAmount::parse('0.20'))->toBe(20)
        ->and(UsdAmount::parse('0'))->toBe(0)
        ->and(UsdAmount::parse('9999999.99'))->toBe(999999999)
        ->and(UsdAmount::parse('12.3'))->toBe(1230)
        ->and(UsdAmount::parse('12'))->toBe(1200);
});

test('USD rejects malformed negative oversized and imprecise input', function (string $amount): void {
    expect(fn () => UsdAmount::parse($amount))->toThrow(InvalidArgumentException::class);
})->with(['-1', '1.001', '1e3', '10000000', 'USD 10', '1,000.00', 'abc', '']);

test('USD display supports signed aggregate amounts without rounding', function (): void {
    expect(UsdAmount::decimal(-30))->toBe('-0.30')->and(UsdAmount::decimal(30))->toBe('0.30')
        ->and(UsdAmount::display(30))->toBe('0.30 USD')->and(UsdAmount::decimal(99999999900))->toBe('999999999.00');
});
