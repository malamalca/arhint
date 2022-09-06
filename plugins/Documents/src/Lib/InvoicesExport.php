<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Lil\Lib\LilPdfFactory;

class InvoicesExport
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
        $this->view->setTemplatePath('Invoices');
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
            $conditions['Invoices.id IN'] = (array)$filter['id'];
        }

        /** @var \Documents\Model\Table\InvoicesTable $Invoices */
        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $params = $Invoices->filter($filter);

        $defaultParams = [
            'conditions' => $conditions,
            'contain' => [
                'InvoicesItems',
                'DocumentsCounters',
                'InvoicesTaxes',
                'Receivers',
                'Buyers',
                'Issuers',
                'DocumentsAttachments',
                'DocumentsLinks' => ['Invoices'],
                'TplHeaders', 'TplBodies', 'TplFooters',
            ],
            'order' => ['Invoices.counter'],
        ];

        $params = array_merge_recursive($defaultParams, $params);
        $documents = $Invoices
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
     * @param array $invoices Array of invoices
     * @return mixed
     */
    public function export($ext, $invoices)
    {
        $result = null;

        switch ($ext) {
            case 'html':
            case 'pdf':
            case 'xml':
                $pdf = null;

                if ($ext == 'pdf') {
                    $pdfEngine = Configure::read('Lil.pdfEngine');
                    $pdfOptions = Configure::read('Lil.' . $pdfEngine);
                    $pdf = LilPdfFactory::create($pdfEngine, (array)$pdfOptions);
                }

                $responseHtml = '';

                foreach ($invoices as $invoice) {
                    $this->view->set('invoices', [0 => $invoice]);

                    $this->view->setTemplate('generic');
                    if (get_class($invoice) == \Documents\Model\Entity\Invoice::class) {
                        $this->view->setTemplate('eslog');
                    }

                    $outputHtml = $this->view->render();

                    if (in_array($ext, ['html', 'xml', 'eslog'])) {
                        $responseHtml .= $outputHtml;
                    }
                    if ($ext == 'pdf') {
                        // PDF
                        $pageOptions = [];
                        if (!empty($invoice->tpl_header)) {
                            $templateBody = $invoice->tpl_header->body;
                            if (substr($templateBody, 0, 5) == 'data:') {
                                $templateBody = json_encode([
                                    'image' => substr($templateBody, strpos($templateBody, ',') + 1),
                                ]);
                            } else {
                                $templateBody = $this->_autop($templateBody);
                            }
                            $pdf->setHeaderHtml($templateBody);
                        }
                        if (!empty($invoice->tpl_footer)) {
                            $templateBody = $invoice->tpl_footer->body;
                            if (substr($templateBody, 0, 5) == 'data:') {
                                $templateBody = json_encode([
                                    'image' => substr($templateBody, strpos($templateBody, ',') + 1),
                                ]);
                            } else {
                                $templateBody = $this->_autop($templateBody);
                            }

                            $pdf->setFooterHtml($templateBody);
                        }
                        $pdf->newPage($this->toHtml($invoice, $outputHtml), $pageOptions);
                    }
                }

                if (in_array($ext, ['html', 'xml', 'eslog'])) {
                    if ($ext == 'html') {
                        $result = $this->toHtml($invoices[0], $responseHtml);
                    } else {
                        $result = $responseHtml;
                    }
                } else {
                    $tmpFilename = constant('TMP') . uniqid('xml2pdf') . '.pdf';
                    if (!$pdf->saveAs($tmpFilename)) {
                        $this->lastError = $pdf->getError();

                        return false;
                    }
                    $result = file_get_contents($tmpFilename);
                    unlink($tmpFilename);
                }
                break;
            case 'eslog20':
                $responseHtml = '';
                foreach ($invoices as $invoice) {
                    $this->view->set('invoices', [0 => $invoice]);
                    $this->view->setTemplate('generic');
                    $this->view->setTemplate('eslog20');

                    $responseHtml .= $this->view->render();
                }

                $result = $responseHtml;
                break;
            default:
                $this->view->set(compact('invoices'));
                $viewCtp = 'eslog';
                if ($ext == 'sepa') {
                    $viewCtp = 'sepa';
                }
                $result = $this->view->render($viewCtp);
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
            case 'eslog':
                $ext = 'eslog.xml';
                break;
            case 'eslog20':
                $ext = 'eslog20.xml';
                $result = $result->withType('xml');
                break;
            case 'sepa':
                $ext = 'sepa.xml';
                $result = $result->withType('xml');
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
            if (get_class($document) == \Documents\Model\Entity\Invoice::class) {
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

    /**
     * Replaces double line-breaks with paragraph elements.
     *
     * A group of regex replaces used to identify text formatted with newlines and
     * replace double line-breaks with HTML paragraph tags. The remaining
     * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
     * or 'false'.
     *
     * @since 0.71
     * @param string $pee The text which has to be formatted.
     * @param int|bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
     * @return string Text which has been converted into correct paragraph tags.
     */
    private function _autop($pee, $br = 1)
    {
        if (trim($pee) === '') {
            return '';
        }
        $pee = $pee . "\n"; // just to make things a little easier, pad the end
        $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        // Space things out a little
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form' .
            '|map|area|blockquote|address|math|style|input|p|h[1-6]|hr)';
        $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = str_replace(["\r\n", "\r"], "\n", $pee); // cross-platform newlines
        if (strpos($pee, '<object') !== false) {
            $pee = preg_replace('|\s*<param([^>]*)>\s*|', '<param$1>', $pee); // no pee inside object/embed
            $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
        }
        $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
        // make paragraphs, including one at the end
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }
        $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', '<p>$1</p></$2>', $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee); // don't pee all over a tag
        $pee = preg_replace('|<p>(<li.+?)</p>|', '$1', $pee); // problem with nested lists
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', '<blockquote$1><p>', $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', '$1', $pee);
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee);
        if ($br) {
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', function ($matches) {
                return str_replace("\n", '<PreserveNewline />', $matches[0]);
            }, $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<PreserveNewline />', "\n", $pee);
        }
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        return $pee;
    }
}
