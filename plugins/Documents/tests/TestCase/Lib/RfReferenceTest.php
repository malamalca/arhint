<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Documents\Lib\RfReference;
use PHPUnit\Framework\Attributes\DataProvider;

class RfReferenceTest extends TestCase
{
    /**
     * Examples from annex 4 of the ZBS rules (version 1.4).
     *
     * @return void
     */
    public function testReferenceFromRules(): void
    {
        $this->assertSame('RF712348231', RfReference::reference('2348231'));
        $this->assertSame('RF45SBO2010', RfReference::reference('SBO2010'));

        $this->assertSame('71', RfReference::checkDigits('2348231'));
        $this->assertSame('45', RfReference::checkDigits('SBO2010'));

        // characters that are not allowed are dropped, lower case is kept as upper case
        $this->assertSame('RF45SBO2010', RfReference::reference('sbo-2010'));

        $this->assertNull(RfReference::reference('---'));
        $this->assertNull(RfReference::checkDigits(str_repeat('1', RfReference::MAX_CONTENT_LENGTH + 1)));
    }

    /**
     * Control of the calculation from annex 5 of the rules.
     *
     * @return void
     */
    public function testIsValidFromRules(): void
    {
        $this->assertTrue(RfReference::isValid('RF712348231'));
        $this->assertTrue(RfReference::isValid('RF45SBO2010'));
        $this->assertTrue(RfReference::isValid('RF71 2348 231'));
        $this->assertTrue(RfReference::isValid('rf45sbo2010'));

        $this->assertFalse(RfReference::isValid('RF702348231'));
        $this->assertFalse(RfReference::isValid('RF442348231'));
        $this->assertFalse(RfReference::isValid('SI0012'));

        // a wrong check digit passes when the check digits are not verified
        $this->assertTrue(RfReference::isValid('RF702348231', false));
    }

    /**
     * @return void
     */
    public function testElectronicAndVisual(): void
    {
        $this->assertSame('RF712348231', RfReference::electronic(' rf71 2348 231 '));
        $this->assertSame('RF71 2348 231', RfReference::visual('RF712348231'));
        $this->assertSame('RF45 SBO2 010', RfReference::visual('RF45 SBO2 010'));
    }

    /**
     * @return void
     */
    public function testIsSelected(): void
    {
        $this->assertTrue(RfReference::isSelected('RF'));
        $this->assertTrue(RfReference::isSelected(' rf '));
        $this->assertFalse(RfReference::isSelected('12'));
        $this->assertFalse(RfReference::isSelected(null));
    }

    /**
     * @return void
     */
    public function testParse(): void
    {
        $this->assertSame(
            ['checkDigits' => '71', 'content' => '2348231'],
            RfReference::parse('RF71 2348 231'),
        );
        $this->assertNull(RfReference::parse('SI0012'));
        $this->assertNull(RfReference::parse('RFAB2348231'));
    }

    /**
     * @return array<array{0: string, 1: string|null}>
     */
    public static function referenceProvider(): array
    {
        return [
            // reference, expected error
            ['RF712348231', null],
            ['RF45SBO2010', null],
            ['SI0012345', RfReference::ERROR_PREFIX],
            ['RF71', RfReference::ERROR_EMPTY],
            ['RF712348231-', RfReference::ERROR_CHARS],
            ['RF71234.8231', RfReference::ERROR_CHARS],
            ['RF71' . str_repeat('1', RfReference::MAX_CONTENT_LENGTH + 1), RfReference::ERROR_LENGTH],
            ['RF702348231', RfReference::ERROR_CHECK_DIGITS],
        ];
    }

    /**
     * @param string $reference Whole reference.
     * @param string|null $expected Expected error.
     * @return void
     */
    #[DataProvider('referenceProvider')]
    public function testValidate(string $reference, ?string $expected): void
    {
        $this->assertSame($expected, RfReference::validate($reference), $reference);
    }

    /**
     * @return void
     */
    public function testValidateContent(): void
    {
        $this->assertNull(RfReference::validateContent('71', '2348231'));
        $this->assertSame(RfReference::ERROR_CHECK_DIGITS, RfReference::validateContent('70', '2348231'));
        $this->assertSame(RfReference::ERROR_CHECK_DIGITS, RfReference::validateContent('7', '2348231'));
        $this->assertSame(RfReference::ERROR_CHECK_DIGITS, RfReference::validateContent('AB', '2348231'));
        $this->assertSame(RfReference::ERROR_CHARS, RfReference::validateContent('71', '2348-231'));
        $this->assertNull(RfReference::validateContent('70', '2348231', false));
    }
}
