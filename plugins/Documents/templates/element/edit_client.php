<?php

function clientFields($kind, $model, $lock = true)
{
    $defType = 'hidden';
    $ret = [
        $kind . '-model' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.model', ['type' => $defType, 'value' => $model],
            ],
        ],
        $kind . '-contact_id' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.contact_id', ['type' => $defType],
            ],
        ],
        $kind . '-contact_id-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.contact_id'],
        ],

        $kind . '-kind' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.kind', ['type' => $defType],
            ],
        ],
        $kind . '-kind-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.kind'],
        ],

        $kind . '-mat_no' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.mat_no', ['type' => $defType],
            ],
        ],
        $kind . '-mat_no-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.mat_no'],
        ],

        $kind . '-tax_no' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.tax_no', ['type' => $defType],
            ],
        ],
        $kind . '-tax_no-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.tax_no'],
        ],

        $kind . '-street' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.street', ['type' => $defType],
            ],
        ],
        $kind . '-street-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.street'],
        ],

        $kind . '-city' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.city', ['type' => $defType],
            ],
        ],
        $kind . '-city-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.city'],
        ],

        $kind . '-zip' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.zip', ['type' => $defType],
            ],
        ],
        $kind . '-zip-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.zip'],
        ],

        $kind . '-country' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.country', ['type' => $defType],
            ],
        ],
        $kind . '-country-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.country'],
        ],

        $kind . '-country_code' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.country_code', ['type' => $defType],
            ],
        ],
        $kind . '-country_code-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.country_code'],
        ],

        $kind . '-iban' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.iban', ['type' => $defType],
            ],
        ],
        $kind . '-iban-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.iban'],
        ],

        $kind . '-bic' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.bic', ['type' => $defType],
            ],
        ],
        $kind . '-bic-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.bic'],
        ],

        $kind . '-bank' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.bank', ['type' => $defType],
            ],
        ],
        $kind . '-bank-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.bank'],
        ],

        $kind . '-person' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.person', ['type' => $defType],
            ],
        ],
        $kind . '-person-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.person'],
        ],

        $kind . '-phone' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.phone', ['type' => $defType],
            ],
        ],
        $kind . '-phone-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.phone'],
        ],

        $kind . '-fax' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.fax', ['type' => $defType],
            ],
        ],
        $kind . '-fax-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.fax'],
        ],

        $kind . '-email' => [
            'method' => 'control',
            'parameters' => [
                'field' => $kind . '.email', ['type' => $defType],
            ],
        ],
        $kind . '-email-unlock' => $lock ? null : [
            'method' => 'unlockField',
            'parameters' => [$kind . '.email'],
        ],
    ];

    return $ret;
}
