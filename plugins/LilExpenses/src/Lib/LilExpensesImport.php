<?php
declare(strict_types=1);

namespace LilExpenses\Lib;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Utility\Xml;
use DirectoryIterator;
use ZipArchive;

class LilExpensesImport
{
    /**
     * @var string $ownerId Owner id.
     */
    private $ownerId;

    /**
     * Default contructor
     *
     * @param string $ownerId Owner id.
     * @return void
     */
    public function __construct($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * Read payments from cache
     *
     * @return array
     */
    public function getPayments()
    {
        $cachedPayments = Cache::remember(
            'LilExpenses.sepaImportedPayments' . $this->ownerId,
            function () {
                return [];
            }
        );

        if (!empty($cachedPayments)) {
            $Payments = TableRegistry::getTableLocator()->get('LilExpenses.Payments');
            $linkedPayments = $Payments->find('list', ['valueField' => 'sepa_id'])
                ->where([
                    'Payments.owner_id' => $this->ownerId,
                    'Payments.sepa_id IN' => array_keys($cachedPayments),
                ])
                ->toArray();

            foreach ($linkedPayments as $id => $sepa_id) {
                $cachedPayments[$sepa_id]['payment_id'] = $id;
            }
        }

         return $cachedPayments;
    }

    /**
     * Clear payments cache
     *
     * @return void
     */
    public function clear()
    {
        Cache::delete('LilExpenses.sepaImportedPayments' . $this->ownerId);
    }

    /**
     * Import payments from file to cache
     *
     * @param string $filename Filename in zip or xml format
     * @param string $ext File extension
     * @return void
     */
    public function addFromFile($filename, $ext)
    {
        switch ($ext) {
            case 'zip':
                $zip = new ZipArchive();
                if ($zip->open($filename) === true) {
                    $tempDir = tempnam(sys_get_temp_dir(), '');
                    if (file_exists($tempDir)) {
                        unlink($tempDir);
                    }
                    mkdir($tempDir);

                    $zip->extractTo($tempDir);
                    $zip->close();

                    foreach (new DirectoryIterator($tempDir) as $fileInfo) {
                        if ($fileInfo->isFile()) {
                            $this->addFromFile(
                                $fileInfo->getPathname(),
                                pathinfo($fileInfo->getPathname(), PATHINFO_EXTENSION)
                            );
                            unlink($fileInfo->getPathname());
                        }
                    }

                    rmdir($tempDir);
                }
                break;
            case 'xml':
                $this->addFromXml($filename);
                break;
        }
    }

    /**
     * Import payments from xml
     *
     * @param string $filename XML filename.
     * @return bool
     */
    public function addFromXml($filename)
    {
        if (file_exists($filename)) {
            try {
                $xmlObject = Xml::build(file_get_contents($filename));
            } catch (\Cake\Utility\Exception\XmlException $e) {
                return false;
            }
            $importedPayments = Xml::toArray($xmlObject);

            $cachedPayments = $this->getPayments();
            if (!isset($importedPayments['Document']['BkToCstmrStmt']['Stmt']['Ntry'][0])) {
                $importedPayments['Document']['BkToCstmrStmt']['Stmt']['Ntry'] = [
                    $importedPayments['Document']['BkToCstmrStmt']['Stmt']['Ntry'],
                ];
            }
            foreach ($importedPayments['Document']['BkToCstmrStmt']['Stmt']['Ntry'] as $Ntry) {
                $RltdPties = $Ntry['CdtDbtInd'] == 'DBIT' ? 'Cdtr' : 'Dbtr';
                $payment = [
                    'id' => $Ntry['AcctSvcrRef'] ?? '',
                    // DBIT - breme, CRDT - dobro
                    'kind' => $Ntry['CdtDbtInd'],
                    'ref' => $Ntry['NtryDtls']['TxDtls']['RmtInf']['Strd']['CdtrRefInf']['Ref'] ?? '',
                    'date' => $Ntry['BookgDt']['Dt'] ?? '',
                    'client' => $Ntry['NtryDtls']['TxDtls']['RltdPties'][$RltdPties]['Nm'],
                    'amount' => $Ntry['Amt']['@'] ?? '',
                    'descr' => $Ntry['NtryDtls']['TxDtls']['RmtInf']['Strd']['AddtlRmtInf'] ?? '',
                ];

                if ($payment['kind'] == 'DBIT') {
                    $payment['amount'] *= -1;
                }

                if (!empty($Ntry['NtryDtls']['TxDtls']['RmtInf']['Ustrd'])) {
                    $payment['descr'] = $Ntry['NtryDtls']['TxDtls']['RmtInf']['Ustrd'];
                }

                $cachedPayments[$payment['id']] = $payment;
            }

            Cache::write('LilExpenses.sepaImportedPayments' . $this->ownerId, $cachedPayments);

            return true;
        }

        return false;
    }
}
