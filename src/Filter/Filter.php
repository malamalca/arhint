<?php
declare(strict_types=1);

namespace App\Filter;

use Cake\Validation\ValidatorAwareInterface;
use Cake\Validation\ValidatorAwareTrait;

abstract class Filter implements ValidatorAwareInterface
{
    use ValidatorAwareTrait;

    /**
     * The alias this object is assigned to validators as.
     *
     * @var string
     */
    public const VALIDATOR_PROVIDER_NAME = 'filter';

    /**
     * @var string
     */
    private string $queryString;

    /**
     * @var array<int,string>
     */
    private array $validFields;

    /**
     * @var array<string,mixed>
     */
    private array $fields;

    /**
     * @var array<string,mixed>
     */
    private array $errors;

    /**
     * Class constructor
     *
     * @param string $queryString Query string
     */
    public function __construct(string $queryString)
    {
        $this->queryString = $queryString;
        $this->fields = $this->parseQuery($queryString);

        $this->initialize();

        $this->errors = $this->getValidator('default')->validate($this->fields['fields']);
    }

    /**
     * Initialize function
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * Returns array of errors
     *
     * @return array<string,mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns array of valid fields
     *
     * @return array<int,string>
     */
    public function getValidFields(): array
    {
        return $this->validFields;
    }

    /**
     * Returns array of fields
     *
     * @return array<string,mixed>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns query string
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Add field list
     *
     * @param string $fieldName Field name
     * @return void
     */
    public function addField(string $fieldName): void
    {
        $this->validFields[] = $fieldName;
    }

    /** Checks filter status
     *
     * @param string $fieldName Field name
     * @param string $fieldValue FieldValue
     * @return bool
     */
    public function check(string $fieldName, string $fieldValue): bool
    {
        return isset($this->fields['fields'][$fieldName]) &&
            strtolower($this->fields['fields'][$fieldName]) == strtolower($fieldValue);
    }

    /** Get field value
     *
     * @param string $fieldName Field name
     * @return mixed
     */
    public function get(string $fieldName): mixed
    {
        return $this->fields['fields'][$fieldName] ?? null;
    }

    /** Delete field value
     *
     * @param string $fieldName Field name
     * @return void
     */
    public function delete(string $fieldName): void
    {
        if (isset($this->fields['fields'][$fieldName])) {
            unset($this->fields['fields'][$fieldName]);
        }
    }

    /** Checks right part of filter status
     *
     * @param string $fieldName Field name
     * @param string $fieldValue FieldValue
     * @return bool
     */
    public function checkRight(string $fieldName, string $fieldValue): bool
    {
        return isset($this->fields['fields'][$fieldName]) &&
            substr(strtolower($this->fields['fields'][$fieldName]), 0, -strlen($fieldValue)) == strtolower($fieldValue);
    }

    /**
     * This function parses query string like milestone:"Testni milestone" status:closed term
     * into usable fields
     *
     * @param string $query Query string
     * @return array<string, mixed>
     */
    private function parseQuery(string $query): array
    {
        preg_match_all('/(\w+):"([^"]+)"|(\w+):(\S+)|(\S+)/', $query, $matches, PREG_SET_ORDER);

        $fields = [];
        $terms = [];

        foreach ($matches as $m) {
            if (!empty($m[1])) {
                $fields[$m[1]] = $m[2] ?? null;
            } elseif (!empty($m[3])) {
                $fields[$m[3]] = $m[4] ?? null;
            } else {
                $terms[] = $m[5] ?? null;
            }
        }

        return ['fields' => $fields, 'terms' => $terms];
    }

    /**
     * Builds query string for tasks filtering
     *
     * @param string $fieldName Field name to modify
     * @param string|null $newValue Field's new value or remove field if null
     * @return string
     */
    public function buildQuery(string $fieldName, ?string $newValue): string
    {
        $fields = $this->fields;

        if (isset($fields['fields'][$fieldName])) {
            if (is_null($newValue)) {
                unset($fields['fields'][$fieldName]);
            } else {
                $fields['fields'][$fieldName] = $newValue;
            }
        } else {
            if (!is_null($newValue)) {
                $fields['fields'][$fieldName] = $newValue;
            }
        }

        $q = '';
        if (isset($fields['fields'])) {
            foreach ($fields['fields'] as $fieldName => $fieldValue) {
                $q .= ($q == '' ? '' : ' ') . $fieldName . ':' . self::escapeQueryArgument($fieldValue);
            }
        }

        if (isset($fields['term'])) {
            if (!empty($fields['term'])) {
                $q .= ($q == '' ? '' : ' ') . implode(' ', (array)$fields['term']);
            }
        }

        return $q;
    }

    /**
     * Escapes query argument if needed
     *
     * @param string $argument Argument to escape
     * @return string
     */
    public static function escapeQueryArgument(string $argument): string
    {
        if (preg_match('/\s/', $argument)) {
            return '"' . str_replace('"', '\"', $argument) . '"';
        }

        return $argument;
    }
}
