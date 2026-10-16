<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Documents\Lib\SiReference;
use PHPUnit\Framework\Attributes\DataProvider;

class SiReferenceTest extends TestCase
{
    /**
     * Check digit examples from annex 3 of the ZBS rules (version 1.4).
     *
     * @return void
     */
    public function testCheckDigitFromRules(): void
    {
        // 1*7 + 0*6 + 2*5 + 6*4 + 7*3 + 4*2 = 70; 70 % 11 = 4; 11 - 4 = 7
        $this->assertSame(7, SiReference::checkDigit('102674'));
        $this->assertSame('1026747', SiReference::appendCheckDigit('102674'));

        // 1*3 + 4*2 = 11; 11 % 11 = 0; 11 - 0 = 11 => 0
        $this->assertSame(0, SiReference::checkDigit('14'));
        $this->assertSame('140', SiReference::appendCheckDigit('14'));

        // 5*3 + 4*2 = 23; 23 % 11 = 1; 11 - 1 = 10 => 0
        $this->assertSame(0, SiReference::checkDigit('54'));
        $this->assertSame('540', SiReference::appendCheckDigit('54'));

        $this->assertTrue(SiReference::hasValidCheckDigit('1026747'));
        $this->assertFalse(SiReference::hasValidCheckDigit('1026744'));
        $this->assertFalse(SiReference::hasValidCheckDigit(''));
    }

    /**
     * @return void
     */
    public function testNormalizeModel(): void
    {
        $this->assertSame('12', SiReference::normalizeModel('12'));
        $this->assertSame('12', SiReference::normalizeModel('SI12'));
        $this->assertSame('99', SiReference::normalizeModel(' si99 '));
        $this->assertSame('00', SiReference::normalizeModel('0'));
        $this->assertNull(SiReference::normalizeModel('13'));
        $this->assertNull(SiReference::normalizeModel('SI'));
        $this->assertNull(SiReference::normalizeModel(''));
        $this->assertNull(SiReference::normalizeModel(null));
        $this->assertNull(SiReference::normalizeModel('1a'));
    }

    /**
     * @return void
     */
    public function testCanGenerate(): void
    {
        $this->assertTrue(SiReference::canGenerate('12'));
        $this->assertTrue(SiReference::canGenerate('SI00'));
        $this->assertFalse(SiReference::canGenerate('11'));
        $this->assertFalse(SiReference::canGenerate(null));
    }

    /**
     * Model 12 content is a single part of exactly 13 digits, padded with leading zeros.
     *
     * @return void
     */
    public function testModel12(): void
    {
        // 000000202602 => 2*2 + 6*4 + 2*5 + 2*7 = 52; 52 % 11 = 8; 11 - 8 = 3
        $this->assertSame('0000002026023', SiReference::model12('202602'));
        $this->assertTrue(SiReference::isValidContent('12', (string)SiReference::model12('202602')));
        $this->assertSame(SiReference::MODEL_12_DIGITS, strlen((string)SiReference::model12('1')));

        // longer data is reduced to its last 12 digits
        $long = (string)SiReference::model12('1234567890123456');
        $this->assertSame(SiReference::appendCheckDigit('567890123456'), $long);
        $this->assertTrue(SiReference::isValidContent('12', $long));

        $this->assertNull(SiReference::model12('ISU-'));
    }

    /**
     * @return void
     */
    public function testGenerate(): void
    {
        $this->assertSame('0000002026023', SiReference::generate('SI12', 'ISU-202602'));
        $this->assertSame('202602', SiReference::generate('00', '202602'));
        $this->assertNull(SiReference::generate('11', '202602'));
        $this->assertNull(SiReference::generate('12', 'ISU'));
        $this->assertNull(SiReference::generate('13', '202602'));

        // model 00 content is limited to 20 digits
        $this->assertSame(
            SiReference::MAX_CONTENT_DIGITS,
            strlen((string)SiReference::generate('00', str_repeat('1', 25))),
        );
    }

    /**
     * Example of a valid reference from chapter 2.1 of the rules.
     *
     * @return void
     */
    public function testIsValidExampleFromRules(): void
    {
        $this->assertTrue(SiReference::isValid('SI0519-1235-84503'));
        $this->assertTrue(SiReference::isValid('SI05 19-1235-84503'));
        $this->assertTrue(SiReference::isValid('SI99'));
        $this->assertTrue(SiReference::isValid('SI000123456'));
        $this->assertFalse(SiReference::isValid('SI1319-1235-84503'));
        $this->assertFalse(SiReference::isValid('RF712348231'));
        $this->assertFalse(SiReference::isValid('123456'));
    }

    /**
     * @return void
     */
    public function testParseAndReference(): void
    {
        $this->assertSame(
            ['model' => '12', 'content' => '0000002026023'],
            SiReference::parse('SI120000002026023'),
        );
        $this->assertNull(SiReference::parse('RF712348231'));
        $this->assertSame('SI120000002026023', SiReference::reference('12', '0000002026023'));
        $this->assertSame('SI99', SiReference::reference('99', null));
    }

    /**
     * @return array<array{0: string, 1: string, 2: string|null}>
     */
    public static function contentProvider(): array
    {
        return [
            // model, content, expected error
            ['00', '123456', null],
            ['00', '01', null],
            ['00', '12-345-678', null],
            ['00', '12-012', SiReference::ERROR_LEADING_ZEROS],
            ['00', '1-2-3-4', SiReference::ERROR_CHARS],
            ['00', '12A', SiReference::ERROR_CHARS],
            ['00', '12 34', SiReference::ERROR_CHARS],
            ['00', '', SiReference::ERROR_EMPTY],
            ['00', '123456789012345678901', SiReference::ERROR_DIGITS],
            ['00', '1234567890123-1', SiReference::ERROR_PART_LENGTH],
            ['05', '19-1235-84503', null],
            ['05', '18-1235-84503', SiReference::ERROR_CHECK_DIGIT],
            ['11', '19-19-7', null],
            ['11', '19-18-7', SiReference::ERROR_CHECK_DIGIT],
            ['11', '19', SiReference::ERROR_PARTS],
            ['12', '0000002026023', null],
            ['12', '0000002026020', SiReference::ERROR_CHECK_DIGIT],
            ['12', '1234', SiReference::ERROR_PART_LENGTH],
            ['12', '19-19', SiReference::ERROR_PARTS],
            ['99', '', null],
            ['99', '123', SiReference::ERROR_NOT_EMPTY],
            ['13', '123', SiReference::ERROR_MODEL],
        ];
    }

    /**
     * @param string $model Model number.
     * @param string $content Model content.
     * @param string|null $expected Expected error.
     * @return void
     */
    #[DataProvider('contentProvider')]
    public function testValidateContent(string $model, string $content, ?string $expected): void
    {
        $this->assertSame(
            $expected,
            SiReference::validateContent($model, $content),
            sprintf('Model %s, content "%s"', $model, $content),
        );
    }
}
