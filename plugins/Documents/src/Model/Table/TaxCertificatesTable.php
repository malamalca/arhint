<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\Utility\Security;
use Documents\Lib\FursClient;
use Documents\Lib\FursXml;
use Documents\Model\Entity\TaxCertificate;

/**
 * TaxCertificates Model - per user FURS signing certificate.
 *
 * @method \Documents\Model\Entity\TaxCertificate newEmptyEntity()
 * @method \Documents\Model\Entity\TaxCertificate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class TaxCertificatesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('documents_tax_certificates');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

    /**
     * Find certificate of user.
     *
     * @param string $userId User id.
     * @return \Documents\Model\Entity\TaxCertificate|null
     */
    public function findForUser(string $userId): ?TaxCertificate
    {
        /** @var \Documents\Model\Entity\TaxCertificate|null $cert */
        $cert = $this->find()->where(['user_id' => $userId])->first();

        return $cert;
    }

    /**
     * Validate and store certificate. The password is stored encrypted.
     *
     * @param string $userId User id.
     * @param string|null $p12 Binary p12 contents; null keeps the stored certificate (password is then required).
     * @param string $password Private key password.
     * @param string|null $taxNo Operator's tax number.
     * @return \Documents\Model\Entity\TaxCertificate|null Null when p12 cannot be opened with password.
     */
    public function store(string $userId, ?string $p12, string $password, ?string $taxNo): ?TaxCertificate
    {
        $cert = $this->findForUser($userId) ?? $this->newEmptyEntity();
        $cert->user_id = $userId;
        $cert->tax_no = FursXml::normalizeTaxNo($taxNo);

        if ($p12 === null) {
            $p12 = $cert->p12 ? (string)base64_decode($cert->p12) : '';
        }

        $info = $p12 === '' ? null : (new FursClient($p12, $password))->certificateInfo();
        if ($info === null) {
            return null;
        }

        $cert->p12 = base64_encode($p12);
        $cert->password = self::encryptPassword($password);
        $cert->valid_to = isset($info['validTo_time_t']) ?
            DateTime::createFromTimestamp($info['validTo_time_t']) :
            null;

        return $this->save($cert) ?: null;
    }

    /**
     * Client for user's certificate.
     *
     * @param string $userId User id.
     * @return \Documents\Lib\FursClient|null Null if user has no certificate.
     */
    public function clientForUser(string $userId): ?FursClient
    {
        $cert = $this->findForUser($userId);
        if (!$cert || empty($cert->p12) || empty($cert->password)) {
            return null;
        }

        return new FursClient((string)base64_decode($cert->p12), self::decryptPassword($cert->password));
    }

    /**
     * Encrypt password for storage.
     *
     * @param string $password Plain password.
     * @return string
     */
    public static function encryptPassword(string $password): string
    {
        return base64_encode(Security::encrypt($password, Security::getSalt()));
    }

    /**
     * Decrypt stored password.
     *
     * @param string $encrypted Stored password.
     * @return string
     */
    public static function decryptPassword(string $encrypted): string
    {
        return (string)Security::decrypt((string)base64_decode($encrypted), Security::getSalt());
    }
}
