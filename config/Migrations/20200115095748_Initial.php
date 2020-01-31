<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class Initial extends AbstractMigration
{
    public function up()
    {
        $this->table('adremas', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('adremas_contacts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('adrema_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contacts_address_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('street', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('zip', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('city', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('country', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('contacts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => 'T',
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('surname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('mat_no', 'string', [
                'default' => null,
                'limit' => 13,
                'null' => true,
            ])
            ->addColumn('tax_no', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('tax_status', 'boolean', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('company_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('job', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('syncable', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'owner_id',
                    'kind',
                ]
            )
            ->create();

        $this->table('contacts_accounts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('iban', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('bic', 'string', [
                'default' => null,
                'limit' => 8,
                'null' => true,
            ])
            ->addColumn('bank', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('primary', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('contacts_addresses', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('street', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('zip', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('city', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('country_code', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('country', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('primary', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('contacts_emails', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('primary', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('contacts_phones', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => true,
            ])
            ->addColumn('no', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('primary', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('expenses', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('foreign_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('dat_happened', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('net_total', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('total', 'decimal', [
                'default' => '0.00',
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'owner_id',
                    'dat_happened',
                ]
            )
            ->create();

        $this->table('invoices', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contact_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('counter_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('doc_type', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('tpl_header_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('tpl_body_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('tpl_footer_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('invoices_attachment_count', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('counter', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('no', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('signed', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('dat_sign', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('dat_issue', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('dat_service', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('dat_expire', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('dat_approval', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('net_total', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('total', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('inversed_tax', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('pmt_kind', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('pmt_sepa_type', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('pmt_type', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('pmt_module', 'string', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('pmt_ref', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('pmt_descript', 'string', [
                'default' => null,
                'limit' => 140,
                'null' => true,
            ])
            ->addColumn('location', 'string', [
                'default' => null,
                'limit' => 70,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'owner_id',
                    'counter_id',
                    'dat_issue',
                ]
            )
            ->create();

        $this->table('invoices_attachments', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('foreign_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('filename', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('original', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('ext', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('mimetype', 'string', [
                'default' => null,
                'limit' => 30,
                'null' => true,
            ])
            ->addColumn('filesize', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('height', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('width', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('invoices_clients', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('invoice_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('street', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('city', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('zip', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('country', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('country_code', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('iban', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('bank', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('tax_no', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('mat_no', 'string', [
                'default' => null,
                'limit' => 35,
                'null' => true,
            ])
            ->addColumn('person', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('phone', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('fax', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'invoice_id',
                ]
            )
            ->create();

        $this->table('invoices_counters', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('doc_type', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('expense', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('counter', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('mask', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('layout', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('layout_title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('template_descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('tpl_header_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('tpl_body_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('tpl_footer_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('invoices_items')
            ->addColumn('invoice_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('item_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('vat_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('vat_title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('vat_percent', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 8,
                'scale' => 1,
            ])
            ->addColumn('descript', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('qty', 'decimal', [
                'default' => '0.0000',
                'null' => false,
                'precision' => 17,
                'scale' => 4,
            ])
            ->addColumn('unit', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('price', 'decimal', [
                'default' => '0.0000',
                'null' => false,
                'precision' => 17,
                'scale' => 4,
            ])
            ->addColumn('discount', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('invoices_links', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('link_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('invoice_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('invoices_taxes')
            ->addColumn('invoice_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('vat_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('vat_title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('vat_percent', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 8,
                'scale' => 1,
            ])
            ->addColumn('base', 'decimal', [
                'default' => '0.00',
                'null' => false,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('invoices_templates', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('kind', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('body', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('main', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('items', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('vat_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('descript', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('qty', 'decimal', [
                'default' => '0.0000',
                'null' => false,
                'precision' => 17,
                'scale' => 4,
            ])
            ->addColumn('unit', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('price', 'decimal', [
                'default' => '0.0000',
                'null' => false,
                'precision' => 17,
                'scale' => 4,
            ])
            ->addColumn('discount', 'decimal', [
                'default' => null,
                'null' => false,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('payments', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('account_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('dat_happened', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('descript', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('amount', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('sepa_id', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'owner_id',
                    'dat_happened',
                ]
            )
            ->create();

        $this->table('payments_accounts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('primary', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'owner_id',
                ]
            )
            ->create();

        $this->table('payments_expenses')
            ->addColumn('payment_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('expense_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('projects', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('no', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('lat', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 4,
            ])
            ->addColumn('lon', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 4,
            ])
            ->addColumn('colorize', 'char', [
                'default' => null,
                'limit' => 6,
                'null' => true,
            ])
            ->addColumn('ico', 'text', [
                'default' => null,
                'limit' => 16777215,
                'null' => true,
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('projects_logs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('project_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('projects_workhours', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('project_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('started', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('duration', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('settings')
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('plugin', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('syncroton_content', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('device_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('folder_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('contentid', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('creation_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('creation_synckey', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('is_deleted', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'device_id',
                    'folder_id',
                    'contentid',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'device_id',
                ]
            )
            ->create();

        $this->table('syncroton_data', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('class', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('folder_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('creation_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('last_modified_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('data', 'binary', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->create();

        $this->table('syncroton_data_folder', ['id' => false, 'primary_key' => ['id', 'owner_id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('owner_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('type', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('parent_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('creation_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('last_modified_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('syncroton_device', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('deviceid', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('devicetype', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('owner_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('acsversion', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('policykey', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('policy_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('useragent', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('imei', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('friendlyname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('os', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('oslanguage', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('phonenumber', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('pinglifetime', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('remotewipe', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('pingfolder', 'binary', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('lastsynccollection', 'binary', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addColumn('lastping', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('contactsfilter_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('calendarfilter_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('tasksfilter_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('emailfilter_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addIndex(
                [
                    'owner_id',
                    'deviceid',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('syncroton_folder', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('device_id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('class', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('folderid', 'string', [
                'default' => null,
                'limit' => 254,
                'null' => false,
            ])
            ->addColumn('parentid', 'string', [
                'default' => null,
                'limit' => 254,
                'null' => true,
            ])
            ->addColumn('displayname', 'string', [
                'default' => null,
                'limit' => 254,
                'null' => false,
            ])
            ->addColumn('type', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('creation_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('lastfiltertype', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('supportedfields', 'binary', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addIndex(
                [
                    'device_id',
                    'class',
                    'folderid',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'device_id',
                ]
            )
            ->create();

        $this->table('syncroton_policy', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('policy_key', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('json_policy', 'binary', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('syncroton_synckey', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('device_id', 'string', [
                'default' => '',
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => '',
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('counter', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('lastsync', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('pendingdata', 'binary', [
                'default' => null,
                'limit' => 4294967295,
                'null' => true,
            ])
            ->addIndex(
                [
                    'device_id',
                    'type',
                    'counter',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'device_id',
                ]
            )
            ->create();

        $this->table('tasks', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('folder_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('foreign_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('started', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('deadline', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('completed', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('priority', 'integer', [
                'default' => '0',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('tasks_folders', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => true,
            ])
            ->addColumn('user_created', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 250,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('company_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('username', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('passwd', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('reset_key', 'string', [
                'default' => null,
                'limit' => 200,
                'null' => true,
            ])
            ->addColumn('privileges', 'integer', [
                'default' => '10',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('vats', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('descript', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('percent', 'decimal', [
                'default' => '0.0',
                'null' => false,
                'precision' => 8,
                'scale' => 1,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('syncroton_content')
            ->addForeignKey(
                'device_id',
                'syncroton_device',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                ]
            )
            ->update();

        $this->table('syncroton_folder')
            ->addForeignKey(
                'device_id',
                'syncroton_device',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                ]
            )
            ->update();

        $this->table('syncroton_synckey')
            ->addForeignKey(
                'device_id',
                'syncroton_device',
                'id',
                [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                ]
            )
            ->update();
    }

    public function down()
    {
        $this->table('syncroton_content')
            ->dropForeignKey(
                'device_id'
            )->save();

        $this->table('syncroton_folder')
            ->dropForeignKey(
                'device_id'
            )->save();

        $this->table('syncroton_synckey')
            ->dropForeignKey(
                'device_id'
            )->save();

        $this->table('adremas')->drop()->save();
        $this->table('adremas_contacts')->drop()->save();
        $this->table('contacts')->drop()->save();
        $this->table('contacts_accounts')->drop()->save();
        $this->table('contacts_addresses')->drop()->save();
        $this->table('contacts_emails')->drop()->save();
        $this->table('contacts_phones')->drop()->save();
        $this->table('expenses')->drop()->save();
        $this->table('invoices')->drop()->save();
        $this->table('invoices_attachments')->drop()->save();
        $this->table('invoices_clients')->drop()->save();
        $this->table('invoices_counters')->drop()->save();
        $this->table('invoices_items')->drop()->save();
        $this->table('invoices_links')->drop()->save();
        $this->table('invoices_taxes')->drop()->save();
        $this->table('invoices_templates')->drop()->save();
        $this->table('items')->drop()->save();
        $this->table('payments')->drop()->save();
        $this->table('payments_accounts')->drop()->save();
        $this->table('payments_expenses')->drop()->save();
        $this->table('projects')->drop()->save();
        $this->table('projects_logs')->drop()->save();
        $this->table('projects_workhours')->drop()->save();
        $this->table('settings')->drop()->save();
        $this->table('syncroton_content')->drop()->save();
        $this->table('syncroton_data')->drop()->save();
        $this->table('syncroton_data_folder')->drop()->save();
        $this->table('syncroton_device')->drop()->save();
        $this->table('syncroton_folder')->drop()->save();
        $this->table('syncroton_policy')->drop()->save();
        $this->table('syncroton_synckey')->drop()->save();
        $this->table('tasks')->drop()->save();
        $this->table('tasks_folders')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('vats')->drop()->save();
    }
}
