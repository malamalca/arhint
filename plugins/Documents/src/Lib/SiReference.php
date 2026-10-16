<?php
declare(strict_types=1);

namespace Documents\Lib;

/**
 * Slovenian standardized payment reference (referenca SI).
 *
 * Implements "Pravila za oblikovanje in uporabo standardiziranih referenc pri opravljanju
 * placilnih storitev", Zdruzenje bank Slovenije, version 1.4, in use since 1. 12. 2023:
 *
 * - positions 1 and 2: constant SI,
 * - positions 3 and 4: model number,
 * - positions 5 to 26: model content of at most 22 characters, of those at most 20 digits
 *   and at most two hyphens.
 *
 * The content is written as one, two or three data parts (P1 - P2 - P3), each of at most
 * 12 digits, all parts together at most 20 digits. Model 12 is an exception: a single part
 * of exactly 13 digits including the check digit, without hyphens. Parts P2 and P3 are
 * written without leading zeros. A check digit is the last digit of the data it protects
 * and is calculated by modulus 11.
 *
 * Check digits are verified only where the rules describe a single part protected by its
 * own check digit. Models where one check digit protects a group of parts (01, 06, 08, 09,
 * 10) and models whose structure the rules describe ambiguously (21, 22, 23, 31, 32) pass
 * the general controls only, so a reference is never rejected on an unverifiable structure.
 */
class SiReference
{
    public const PREFIX = 'SI';

    public const MAX_LENGTH = 26;
    public const MAX_CONTENT_LENGTH = 22;
    public const MAX_CONTENT_DIGITS = 20;
    public const MAX_PART_DIGITS = 12;

    public const MODEL_DEFAULT = '12';
    public const MODEL_PLAIN = '00';
    public const MODEL_NONE = '99';
    public const MODEL_12_DIGITS = 13;

    public const ERROR_MODEL = 'model';
    public const ERROR_CHARS = 'chars';
    public const ERROR_EMPTY = 'empty';
    public const ERROR_NOT_EMPTY = 'notEmpty';
    public const ERROR_LENGTH = 'length';
    public const ERROR_DIGITS = 'digits';
    public const ERROR_PARTS = 'parts';
    public const ERROR_PART_LENGTH = 'partLength';
    public const ERROR_LEADING_ZEROS = 'leadingZeros';
    public const ERROR_CHECK_DIGIT = 'checkDigit';

    /**
     * Models from chapter 2.2 of the rules.
     *
     * `min` and `max` are the required and the allowed number of data parts, `checks` are
     * the parts (1 based) that carry their own check digit and are therefore verified.
     */
    public const MODELS = [
        '00' => ['min' => 1, 'max' => 3, 'checks' => []],
        '01' => ['min' => 1, 'max' => 3, 'checks' => []],
        '02' => ['min' => 3, 'max' => 3, 'checks' => [2, 3]],
        '03' => ['min' => 3, 'max' => 3, 'checks' => [1, 2, 3]],
        '04' => ['min' => 3, 'max' => 3, 'checks' => [1, 3]],
        '05' => ['min' => 1, 'max' => 3, 'checks' => [1]],
        '06' => ['min' => 2, 'max' => 3, 'checks' => []],
        '07' => ['min' => 2, 'max' => 3, 'checks' => [2]],
        '08' => ['min' => 3, 'max' => 3, 'checks' => [3]],
        '09' => ['min' => 1, 'max' => 3, 'checks' => []],
        '10' => ['min' => 2, 'max' => 3, 'checks' => [1]],
        '11' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '12' => ['min' => 1, 'max' => 1, 'checks' => [1]],
        '18' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '19' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '21' => ['min' => 2, 'max' => 2, 'checks' => []],
        '22' => ['min' => 2, 'max' => 2, 'checks' => []],
        '23' => ['min' => 2, 'max' => 2, 'checks' => []],
        '28' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '31' => ['min' => 2, 'max' => 2, 'checks' => []],
        '32' => ['min' => 2, 'max' => 2, 'checks' => []],
        '38' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '40' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '41' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '48' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '49' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '51' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '55' => ['min' => 1, 'max' => 3, 'checks' => [1]],
        '58' => ['min' => 2, 'max' => 3, 'checks' => [1, 2]],
        '99' => ['min' => 0, 'max' => 0, 'checks' => []],
    ];

    /**
     * Models whose content this class can generate.
     *
     * @var array<string>
     */
    public const GENERATED_MODELS = [self::MODEL_PLAIN, self::MODEL_DEFAULT];

    /**
     * Checks if model number is one of the models from the rules.
     *
     * @param string $model Two digit model number.
     * @return bool
     */
    public static function isModel(string $model): bool
    {
        return isset(self::MODELS[$model]);
    }

    /**
     * Model number without the SI prefix, padded to two digits.
     *
     * @param string|null $model Model number, with or without the SI prefix (eg SI12, 12, 0).
     * @return string|null Null when the model is not one of the models from the rules.
     */
    public static function normalizeModel(?string $model): ?string
    {
        $model = strtoupper(trim((string)$model));
        $model = (string)preg_replace('/^' . self::PREFIX . '/', '', $model);

        if ($model === '' || preg_match('/^\d{1,2}$/', $model) !== 1) {
            return null;
        }

        $model = str_pad($model, 2, '0', STR_PAD_LEFT);

        return self::isModel($model) ? $model : null;
    }

    /**
     * Checks if content for the model can be generated.
     *
     * @param string|null $model Model number, with or without the SI prefix.
     * @return bool
     */
    public static function canGenerate(?string $model): bool
    {
        return in_array(self::normalizeModel($model), self::GENERATED_MODELS, true);
    }

    /**
     * Check digit of numeric data, calculated by modulus 11 (annex 3 of the rules).
     *
     * Digits are multiplied by weights starting with 2 on the right and increasing by 1
     * towards the left. The sum of the products is divided by 11 and the remainder is
     * subtracted from 11. A result of 10 or 11 gives the check digit 0.
     *
     * @param string $digits Data without the check digit.
     * @return int
     */
    public static function checkDigit(string $digits): int
    {
        $digits = (string)preg_replace('/\D/', '', $digits);

        $sum = 0;
        $weight = 2;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += (int)$digits[$i] * $weight;
            $weight++;
        }

        $check = 11 - ($sum % 11);

        return $check >= 10 ? 0 : $check;
    }

    /**
     * Data with the check digit appended as its last digit.
     *
     * @param string $digits Data without the check digit.
     * @return string
     */
    public static function appendCheckDigit(string $digits): string
    {
        $digits = (string)preg_replace('/\D/', '', $digits);

        return $digits . self::checkDigit($digits);
    }

    /**
     * Checks if the last digit of the data is its correct check digit.
     *
     * @param string $digits Data including the check digit.
     * @return bool
     */
    public static function hasValidCheckDigit(string $digits): bool
    {
        if ($digits === '') {
            return false;
        }

        $data = substr($digits, 0, -1);

        return (int)substr($digits, -1) === self::checkDigit($data);
    }

    /**
     * Content of a model 12 reference: exactly 13 digits including the check digit.
     *
     * Shorter data is padded with leading zeros, longer data is reduced to its last
     * 12 digits.
     *
     * @param string $digits Data without the check digit.
     * @return string|null Null when there are no digits to build the content from.
     */
    public static function model12(string $digits): ?string
    {
        $digits = (string)preg_replace('/\D/', '', $digits);
        if ($digits === '') {
            return null;
        }

        $digits = substr($digits, -(self::MODEL_12_DIGITS - 1));

        return self::appendCheckDigit(str_pad($digits, self::MODEL_12_DIGITS - 1, '0', STR_PAD_LEFT));
    }

    /**
     * Generates the content of a reference for one of the GENERATED_MODELS.
     *
     * @param string $model Model number, with or without the SI prefix.
     * @param string $digits Data the content is built from; non digits are dropped.
     * @return string|null Null when the model is not generated or there are no digits.
     */
    public static function generate(string $model, string $digits): ?string
    {
        $model = self::normalizeModel($model);
        $digits = (string)preg_replace('/\D/', '', $digits);

        if ($model === null || $digits === '') {
            return null;
        }

        return match ($model) {
            self::MODEL_DEFAULT => self::model12($digits),
            self::MODEL_PLAIN => substr($digits, -self::MAX_CONTENT_DIGITS),
            default => null,
        };
    }

    /**
     * Data parts (P1, P2, P3) of the content.
     *
     * @param string $content Model content.
     * @return array<string>
     */
    public static function parts(string $content): array
    {
        return $content === '' ? [] : explode('-', $content);
    }

    /**
     * Whole reference in its electronic form (SI, model number, content).
     *
     * @param string $model Two digit model number.
     * @param string|null $content Model content.
     * @return string
     */
    public static function reference(string $model, ?string $content): string
    {
        return self::PREFIX . $model . (string)$content;
    }

    /**
     * Splits a whole reference into its model number and content.
     *
     * @param string $reference Reference in its electronic form (eg SI0519-1235-84503).
     * @return array{model: string, content: string}|null Null when the reference is not an SI reference.
     */
    public static function parse(string $reference): ?array
    {
        $reference = strtoupper(str_replace(' ', '', trim($reference)));

        if (preg_match('/^' . self::PREFIX . '(\d{2})(.*)$/', $reference, $matches) !== 1) {
            return null;
        }

        return ['model' => $matches[1], 'content' => $matches[2]];
    }

    /**
     * Validates a whole reference.
     *
     * @param string $reference Reference in its electronic form (eg SI0519-1235-84503).
     * @return bool
     */
    public static function isValid(string $reference): bool
    {
        $parsed = self::parse($reference);

        return $parsed !== null && self::validateContent($parsed['model'], $parsed['content']) === null;
    }

    /**
     * Validates the content of a model.
     *
     * @param string $model Two digit model number.
     * @param string $content Model content.
     * @return bool
     */
    public static function isValidContent(string $model, string $content): bool
    {
        return self::validateContent($model, $content) === null;
    }

    /**
     * Validates the content of a model and reports the first broken rule.
     *
     * @param string $model Two digit model number.
     * @param string $content Model content.
     * @param bool $verifyCheckDigits Verify the check digits of the model.
     * @return string|null One of the ERROR_* constants or null when the content is valid.
     */
    public static function validateContent(string $model, string $content, bool $verifyCheckDigits = true): ?string
    {
        if (!self::isModel($model)) {
            return self::ERROR_MODEL;
        }

        $structure = self::MODELS[$model];
        $content = trim($content);

        // model 99 is used without any data
        if ($model === self::MODEL_NONE) {
            return $content === '' ? null : self::ERROR_NOT_EMPTY;
        }

        if ($content === '') {
            return self::ERROR_EMPTY;
        }

        if (preg_match('/^\d+(-\d+){0,2}$/', $content) !== 1) {
            return self::ERROR_CHARS;
        }

        if (strlen($content) > self::MAX_CONTENT_LENGTH) {
            return self::ERROR_LENGTH;
        }

        $parts = self::parts($content);
        if (count($parts) < $structure['min'] || count($parts) > $structure['max']) {
            return self::ERROR_PARTS;
        }

        $maxPartDigits = $model === self::MODEL_DEFAULT ? self::MODEL_12_DIGITS : self::MAX_PART_DIGITS;
        $maxDigits = $model === self::MODEL_DEFAULT ? self::MODEL_12_DIGITS : self::MAX_CONTENT_DIGITS;

        if (strlen(str_replace('-', '', $content)) > $maxDigits) {
            return self::ERROR_DIGITS;
        }

        foreach ($parts as $index => $part) {
            if (strlen($part) > $maxPartDigits) {
                return self::ERROR_PART_LENGTH;
            }
            if ($model === self::MODEL_DEFAULT && strlen($part) !== self::MODEL_12_DIGITS) {
                return self::ERROR_PART_LENGTH;
            }
            // P2 and P3 are written without leading zeros
            if ($index > 0 && strlen($part) > 1 && $part[0] === '0') {
                return self::ERROR_LEADING_ZEROS;
            }
        }

        if ($verifyCheckDigits) {
            foreach ($structure['checks'] as $position) {
                $part = $parts[$position - 1] ?? null;
                if ($part !== null && !self::hasValidCheckDigit($part)) {
                    return self::ERROR_CHECK_DIGIT;
                }
            }
        }

        return null;
    }
}
