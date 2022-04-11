<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

class DocumentsExport
{
    /**
     * @var string $lastError
     */
    public $lastError = null;
    /**
     * @var \Cake\View\View $view
     */
    private $view = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $viewClass = \Cake\View\XmlView::class;
        $viewOptions = [];

        $this->view = new $viewClass(null, null, EventManager::instance(), []);
        $this->view->setTemplatePath('Documents');
        $this->view->setPlugin('Documents');
        $this->view->loadHelper('Lil.Lil');
    }

    /**
     * Find documents for expodrt
     *
     * @param array $filter Array of options
     * @return \Cake\ORM\Query
     */
    public function find($filter)
    {
        $conditions = [];
        if (!empty($filter['id'])) {
            $conditions['Documents.id IN'] = (array)$filter['id'];
        }

        /** @var \Documents\Model\Table\DocumentsTable $DocumentsTable */
        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $params = $DocumentsTable->filter($filter);

        $defaultParams = [
            'conditions' => $conditions,
            'contain' => [
                'DocumentsCounters',
                'Receivers',
                'Issuers',
                'DocumentsAttachments',
                'DocumentsLinks' => ['Invoices'],
                'TplHeaders', 'TplBodies', 'TplFooters',
            ],
            'order' => ['Documents.counter'],
        ];

        $params = array_merge_recursive($defaultParams, $params);
        $documents = $DocumentsTable
            ->find()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order']);

        return $documents;
    }

    /**
     * export method
     *
     * @param string $ext Export extension.
     * @param array $data Array of documents
     * @return mixed
     */
    public function export($ext, $data)
    {
        $result = null;

        switch ($ext) {
            case 'pdf':
                break;
            case 'xml':
                $responseHtml = '';

                foreach ($data as $document) {
                    $this->view->set('documents', [0 => $document]);
                    $this->view->setTemplate('generic');

                    $outputHtml = $this->view->render();

                    if (in_array($ext, ['html', 'xml'])) {
                        $responseHtml .= $outputHtml;
                    }
                }

                if ($ext == 'html') {
                    $result = $this->toHtml($data[0], $responseHtml);
                } else {
                    $result = $responseHtml;
                }
                break;
        }

        return $result;
    }

    /**
     * Returns Cake response object
     *
     * @param string $ext Export extension.
     * @param string $data Result of Export funtion.
     * @param array $options Export options
     * @return \Cake\Http\Response
     */
    public function response($ext, $data, $options = [])
    {
        $defaults = ['download' => false, 'filename' => 'documents'];
        $options = array_merge($defaults, $options);

        $result = new Response();
        $result = $result->withStringBody($data);

        switch ($ext) {
            case 'html':
                $result = $result->withType('html');
                break;
            case 'xml':
                $result = $result->withType('xml');
                $ext = 'xml';
                break;
            case 'pdf':
                $result = $result->withType('pdf');
                break;
        }

        if ($options['download']) {
            $result = $result->withDownload($options['filename'] . '.' . $ext);
        }

        return $result;
    }

    /**
     * Convert XML document to HTML
     *
     * @param object $document Document entity.
     * @param string $eslogXml Document XML in eSlog format.
     * @return mixed
     */
    private function toHtml($document, $eslogXml)
    {
        // load stylesheet for specified document
        $xsl = new \DOMDocument();
        if (!empty($document->tpl_body)) {
            $xsl->loadXml($document->tpl_body->body, LIBXML_NOCDATA);
        } else {
            $xsltTpl = Plugin::path('Documents') . DS . 'webroot' . DS . 'doc_default.xslt';
            if ($document->isInvoice()) {
                $xsltTpl = Plugin::path('Documents') . DS . 'webroot' . DS . 'doc_eslog.xslt';
            }
            $xsl->load($xsltTpl, LIBXML_NOCDATA);
        }

        $xslt = new \XSLTProcessor();
        $xslt->importStylesheet($xsl);

        $xml = new \DOMDocument();
        $xml->loadXML($eslogXml);

        $result = $xslt->transformToXml($xml);
        $event = new Event(
            'Documents.Invoices.Export.Html',
            $document,
            [$result]
        );
        EventManager::instance()->dispatch($event);
        $eventResult = $event->getResult();
        if (!empty($eventResult)) {
            $result = $eventResult;
        }

        return $result;
    }
}
