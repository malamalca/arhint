<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * InvoicesClient Entity.
 *
 * @property string $id
 * @property string|null $invoice_id
 * @property string|null $contact_id
 * @property string|null $kind
 * @property string|null $title
 * @property string|null $street
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $country_code
 * @property string|null $iban
 * @property string|null $bank
 * @property string|null $tax_no
 * @property string|null $mat_no
 * @property string|null $person
 * @property string|null $phone
 * @property string|null $fax
 * @property string|null $email
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class InvoicesClient extends Entity
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
     * Patch InvoicesClient with data from specified user
     *
     * @param \App\Model\Entity\User $user Source data entity.
     * @return void
     */
    public function patchWithAuth($user)
    {
        $this->contact_id = $user->company_id;

        if (empty($user->company)) {
            /** @var \LilCrm\Model\Entity\Contact $company */
            $company = TableRegistry::getTableLocator()->get('LilCrm.Contacts')->get($user->company_id, [
                'contain' => ['PrimaryAddresses', 'PrimaryAccounts'],
            ]);
        } else {
            /** @var \LilCrm\Model\Entity\Contact $company */
            $company = $user->company;
        }
        $this->title = $company->title;
        $this->mat_no = $company->mat_no;
        $this->tax_no = $company->tax_no;

        if (isset($company->primary_address)) {
            $this->street = $company->primary_address->street;
            $this->city = $company->primary_address->city;
            $this->zip = $company->primary_address->zip;
            $this->country = $company->primary_address->country;
            $this->country_code = $company->primary_address->country_code;
        }

        if (isset($company->primary_account)) {
            $this->iban = $company->primary_account->iban;
            $this->bank = $company->primary_account->bank;
            // todo: convert bic to bank name
        }

        $this->person = $user->name;
    }
}
