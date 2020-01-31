<?php
declare(strict_types=1);

namespace LilCrm\Model\Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;

/**
 * Contact Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $kind
 * @property string|null $name
 * @property string|null $surname
 * @property string|null $title
 * @property string|null $descript
 * @property string|null $mat_no
 * @property string|null $tax_no
 * @property string|null $company_id
 * @property string|null $job
 * @property bool $syncable
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \LilCrm\Model\Entity\Contact $company
 * @property \LilCrm\Model\Entity\ContactsAccount $primary_account
 * @property \LilCrm\Model\Entity\ContactsAddress $primary_address
 * @property \LilCrm\Model\Entity\ContactsEmail $primary_email
 * @property \LilCrm\Model\Entity\ContactsPhone $primary_phone
 *
 * @property \LilCrm\Model\Entity\ContactsAccount $contact_accounts
 * @property \LilCrm\Model\Entity\ContactsAddress $contact_addresses
 * @property \LilCrm\Model\Entity\ContactsEmail $contact_emails
 * @property \LilCrm\Model\Entity\ContactsPhone $contact_phones
 */
class Contact extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * toShort method
     *
     * Converts entity to standard array representation.
     *
     * @return array
     */
    public function toShort()
    {
        $ret = [
            'id' => $this->id,
            'title' => $this->title,
            'name' => $this->name,
            'surname' => $this->surname,
            'mat_no' => $this->mat_no,
            'tax_no' => $this->tax_no,
        ];

        if (isset($this->no)) {
            $ret['no'] = $this->no;
        }
        if (isset($this->dob)) {
            $ret['dob'] = $this->dob->format('Y-m-d');
        }
        if (isset($this->plob)) {
            $ret['plob'] = $this->plob;
        }
        if (isset($this->nationality)) {
            $ret['nationality'] = $this->nationality;
        }

        if (isset($this->primary_address)) {
            $countries = Configure::read('LilCrm.countries');
            $country = $this->primary_address->country_code;
            if (isset($countries[$country])) {
                $country = $countries[$country];
            }
            $ret += [
                'street' => $this->primary_address->street,
                'city' => $this->primary_address->city,
                'zip' => $this->primary_address->zip,
                'country' => $country,
                'country_code' => $this->primary_address->country_code,
            ];
        }

        if (isset($this->primary_account)) {
            $ret += [
                'iban' => $this->primary_account->iban,
                'bic' => $this->primary_account->bic,
                'bank' => $this->primary_account->bank,
            ];
        }

        if (isset($this->primary_email)) {
            $ret += [
                'email' => $this->primary_email->email,
            ];
        }

        if (isset($this->primary_phone)) {
            $ret += [
                'phone' => $this->primary_phone->no,
            ];
        }

        return $ret;
    }
}
