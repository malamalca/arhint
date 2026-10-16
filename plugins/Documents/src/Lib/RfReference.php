<?php
declare(strict_types=1);

namespace Documents\Lib;

/**
 * Creditor reference by ISO 11649 (referenca RF).
 *
 * Implements chapter 3 of "Pravila za oblikovanje in uporabo standardiziranih referenc pri
 * opravljanju placilnih storitev", Zdruzenje bank Slovenije, version 1.4:
 *
 * - positions 1 and 2: constant RF,
 * - positions 3 and 4: check digits, calculated by MOD 97-10 (ISO/IEC 7064),
 * - positions 5 to 25: the content, at most 21 digits and letters; hyphens, spaces and
 *   other special characters are not allowed.
 *
 * In its electronic form the reference is written without spaces; visually it is written in
 * groups of four characters, the last group may be shorter. The reference is recommended by
 * the SEPA rules and is used in the whole SEPA area, so it is the reference of choice for
 * cross border payments.
 */
class RfReference
{
    public const PREFIX = 'RF';

    public const MAX_LENGTH = 25;
    public const MAX_CONTENT_LENGTH = 21;
    public const CHECK_DIGITS_LENGTH = 2;
    public const GROUP_LENGTH = 4;

    public const ERROR_PREFIX = 'prefix';
    public const ERROR_EMPTY = 'empty';
    public const ERROR_CHARS = 'chars';
    public const ERROR_LENGTH = 'length';
    public const ERROR_CHECK_DIGITS = 'checkDigits';

    /**
     * Checks if the value selects an RF reference (eg the `pmt_mod` of a counter).
     *
     * @param string|null $value Value to check.
     * @return bool
     */
    public static function isSelected(?string $value): bool
    {
        return strtoupper(trim((string)$value)) === self::PREFIX;
    }

    /**
     * Content without the characters that are not allowed, in upper case.
     *
     * @param string $content Content of the reference.
     * @return string
     */
    public static function normalizeContent(string $content): string
    {
        return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $content));
    }

    /**
     * Reference in its electronic form: upper case and without spaces.
     *
     * @param string $reference Reference in its visual or electronic form.
     * @return string
     */
    public static function electronic(string $reference): string
    {
        return strtoupper((string)preg_replace('/\s+/', '', trim($reference)));
    }

    /**
     * Reference in its visual form: groups of four characters separated by a space.
     *
     * @param string $reference Reference in its visual or electronic form.
     * @return string
     */
    public static function visual(string $reference): string
    {
        return trim((string)chunk_split(self::electronic($reference), self::GROUP_LENGTH, ' '));
    }

    /**
     * Letters converted to numbers for the check digit calculation (A = 10 ... Z = 35).
     *
     * @param string $value Digits and letters.
     * @return string
     */
    protected static function numeric(string $value): string
    {
        $numeric = '';
        foreach (str_split(strtoupper($value)) as $char) {
            $numeric .= ctype_digit($char) ? $char : (string)(ord($char) - ord('A') + 10);
        }

        return $numeric;
    }

    /**
     * Remainder of a number of any length divided by 97.
     *
     * @param string $digits Number as a string of digits.
     * @return int
     */
    protected static function mod97(string $digits): int
    {
        $remainder = 0;
        foreach (str_split($digits, 7) as $chunk) {
            // the remainder is at most two digits, so each step stays within an integer
            $remainder = (int)($remainder . $chunk) % 97;
        }

        return $remainder;
    }

    /**
     * Check digits of the content (annex 4 of the rules).
     *
     * RF00 is appended to the content on the right, letters are converted to numbers, the
     * remainder of the division by 97 is subtracted from 98 and padded to two digits.
     *
     * @param string $content Content of the reference.
     * @return string|null Null when the content is not a valid reference content.
     */
    public static function checkDigits(string $content): ?string
    {
        $content = self::normalizeContent($content);
        if ($content === '' || strlen($content) > self::MAX_CONTENT_LENGTH) {
            return null;
        }

        $check = 98 - self::mod97(self::numeric($content . self::PREFIX . '00'));

        return str_pad((string)$check, self::CHECK_DIGITS_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Whole reference built from the content, in its electronic form.
     *
     * @param string $content Content of the reference; characters that are not allowed are dropped.
     * @return string|null Null when the content is not a valid reference content.
     */
    public static function reference(string $content): ?string
    {
        $content = self::normalizeContent($content);
        $checkDigits = self::checkDigits($content);

        if ($checkDigits === null) {
            return null;
        }

        return self::PREFIX . $checkDigits . $content;
    }

    /**
     * Splits a whole reference into its check digits and content.
     *
     * @param string $reference Reference in its visual or electronic form (eg RF71 2348 231).
     * @return array{checkDigits: string, content: string}|null Null when the reference is not an RF reference.
     */
    public static function parse(string $reference): ?array
    {
        $reference = self::electronic($reference);

        if (preg_match('/^' . self::PREFIX . '(\d{2})(.*)$/', $reference, $matches) !== 1) {
            return null;
        }

        return ['checkDigits' => $matches[1], 'content' => $matches[2]];
    }

    /**
     * Validates a whole reference.
     *
     * @param string $reference Reference in its visual or electronic form.
     * @param bool $verifyCheckDigits Verify the check digits of the reference.
     * @return bool
     */
    public static function isValid(string $reference, bool $verifyCheckDigits = true): bool
    {
        return self::validate($reference, $verifyCheckDigits) === null;
    }

    /**
     * Validates a whole reference and reports the first broken rule.
     *
     * The check digits are verified by moving the first four characters to the end of the
     * reference and dividing it by 97; a remainder of 1 means the reference is valid
     * (annex 5 of the rules).
     *
     * @param string $reference Reference in its visual or electronic form.
     * @param bool $verifyCheckDigits Verify the check digits of the reference.
     * @return string|null One of the ERROR_* constants or null when the reference is valid.
     */
    public static function validate(string $reference, bool $verifyCheckDigits = true): ?string
    {
        $reference = self::electronic($reference);
        $parsed = self::parse($reference);

        if ($parsed === null) {
            return self::ERROR_PREFIX;
        }

        if ($parsed['content'] === '') {
            return self::ERROR_EMPTY;
        }

        if (preg_match('/^[A-Z0-9]+$/', $parsed['content']) !== 1) {
            return self::ERROR_CHARS;
        }

        if (strlen($reference) > self::MAX_LENGTH) {
            return self::ERROR_LENGTH;
        }

        if ($verifyCheckDigits) {
            // the first four characters moved to the end of the reference divide by 97 with
            // the remainder 1
            $moved = $parsed['content'] . self::PREFIX . $parsed['checkDigits'];
            if (self::mod97(self::numeric($moved)) !== 1) {
                return self::ERROR_CHECK_DIGITS;
            }
        }

        return null;
    }

    /**
     * Validates the check digits and the content as they are kept on a document.
     *
     * @param string $checkDigits Check digits of the reference.
     * @param string $content Content of the reference.
     * @param bool $verifyCheckDigits Verify the check digits of the reference.
     * @return string|null One of the ERROR_* constants or null when the reference is valid.
     */
    public static function validateContent(
        string $checkDigits,
        string $content,
        bool $verifyCheckDigits = true,
    ): ?string {
        if (preg_match('/^\d{' . self::CHECK_DIGITS_LENGTH . '}$/', trim($checkDigits)) !== 1) {
            return self::ERROR_CHECK_DIGITS;
        }

        return self::validate(self::PREFIX . trim($checkDigits) . $content, $verifyCheckDigits);
    }
}
