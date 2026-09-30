<?php
declare(strict_types=1);

namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TaxCertificatesFixture - empty table, tests create their own records
 */
class TaxCertificatesFixture extends TestFixture
{
    /**
     * @var string
     */
    public string $table = 'documents_tax_certificates';

    /**
     * Records
     *
     * @var array
     */
    public array $records = [];
}
