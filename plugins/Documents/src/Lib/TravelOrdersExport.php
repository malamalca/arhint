<?php
declare(strict_types=1);

namespace Documents\Lib;

use App\Lib\LilPdfFactory;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\View\View;
use Cake\View\XmlView;
use DOMDocument;
use XSLTProcessor;

class TravelOrdersExport
{
    /**
     * @var string|null $lastError
     */
    public ?string $lastError = null;

    /**
     * @var \Cake\View\View $view
     */
    private View $view;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->view = new XmlView(null, null, EventManager::instance(), []);
        $this->view->setTemplatePath('TravelOrders');
        $this->view->setPlugin('Documents');
        $this->view->loadHelper('Lil');
        $this->view->loadHelper('Number');
    }

    /**
     * Find travel orders for export
     *
     * @param array<string, mixed> $filter Array of filter options
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function find(array $filter): SelectQuery
    {
        /** @var \Documents\Model\Table\TravelOrdersTable $TravelOrders */
        $TravelOrders = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $params = $TravelOrders->filter($filter);

        $defaultParams = [
            'conditions' => [],
            'contain' => [
                'DocumentsCounters',
                'Employees',
                'Payers',
                'TravelOrdersMileages',
                'TravelOrdersExpenses',
                'EnteredBy',
                'ApprovedBy',
                'ProcessedBy',
                'Attachments',
                'TplHeaders',
                'TplBodies',
                'TplFooters',
            ],
            'order' => ['TravelOrders.counter'],
        ];

        $params = array_merge_recursive($defaultParams, $params);

        return $TravelOrders
            ->find()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->orderBy($params['order']);
    }

    /**
     * Export travel orders to the given format
     *
     * @param string $ext Export extension: 'xml', 'html', or 'pdf'.
     * @param array<\Documents\Model\Entity\TravelOrder> $travelOrders Array of travel order entities.
     * @return mixed String data on success, false on failure.
     */
    public function export(string $ext, array $travelOrders): mixed
    {
        $result = null;

        switch ($ext) {
            case 'html':
            case 'pdf':
                $pdf = null;
                if ($ext === 'pdf') {
                    $pdfEngine = Configure::read('Pdf.pdfEngine');
                    $pdfOptions = Configure::read('Pdf.' . $pdfEngine);
                    $pdf = LilPdfFactory::create($pdfEngine, array_merge((array)$pdfOptions));
                }

                foreach ($travelOrders as $travelOrder) {
                    $this->view->set('travelOrders', [$travelOrder]);
                    $this->view->setTemplate('generic');
                    $xmlOutput = $this->view->render();

                    if ($ext === 'html') {
                        $result = $this->toHtml($travelOrder, $xmlOutput);
                    } else {
                        $pageOptions = [];
                        if (!empty($travelOrder->tpl_header)) {
                            $templateBody = $travelOrder->tpl_header->body ?? '';
                            if (substr($templateBody, 0, 5) === 'data:') {
                                $templateBody = json_encode([
                                    'image' => substr($templateBody, strpos($templateBody, ',') + 1),
                                ]);
                            } else {
                                $templateBody = $this->_autop($templateBody);
                            }
                            $pdf->setHeaderHtml((string)$templateBody);
                        }
                        if (!empty($travelOrder->tpl_footer)) {
                            $templateBody = $travelOrder->tpl_footer->body ?? '';
                            if (substr($templateBody, 0, 5) === 'data:') {
                                $templateBody = json_encode([
                                    'image' => substr($templateBody, strpos($templateBody, ',') + 1),
                                ]);
                            } else {
                                $templateBody = $this->_autop($templateBody);
                            }
                            $pdf->setFooterHtml((string)$templateBody);
                        }
                        $pdf->newPage($this->toHtml($travelOrder, $xmlOutput), $pageOptions);
                    }
                }

                if ($ext === 'pdf' && !empty($pdf)) {
                    $tmpFilename = constant('TMP') . uniqid('to2pdf') . '.pdf';
                    if (!$pdf->saveAs($tmpFilename)) {
                        $this->lastError = $pdf->getError();

                        return false;
                    }
                    $result = file_get_contents($tmpFilename);
                    unlink($tmpFilename);
                }
                break;

            case 'xml':
            default:
                $this->view->set('travelOrders', $travelOrders);
                $this->view->setTemplate('generic');
                $result = $this->view->render();
                break;
        }

        return $result;
    }

    /**
     * Returns a Cake HTTP response for the exported data
     *
     * @param string $ext Export extension.
     * @param string $data Exported data string.
     * @param array<string, mixed> $options Response options.
     * @return \Cake\Http\Response
     */
    public function response(string $ext, string $data, array $options = []): Response
    {
        $defaults = ['download' => false, 'filename' => 'travel-orders'];
        $options = array_merge($defaults, $options);

        $result = new Response();
        $result = $result->withStringBody($data);

        switch ($ext) {
            case 'html':
                $result = $result->withType('html');
                break;
            case 'pdf':
                $result = $result->withType('pdf');
                break;
            default:
                $result = $result->withType('xml');
                break;
        }

        if ($options['download']) {
            $result = $result->withDownload($options['filename'] . '.' . $ext);
        }

        return $result;
    }

    /**
     * Transform travel-order XML to HTML via XSLT
     *
     * @param object $travelOrder TravelOrder entity (used to pick tpl_body stylesheet).
     * @param string $xmlData XML string produced by the xml/generic template.
     * @return mixed HTML string.
     */
    private function toHtml(object $travelOrder, string $xmlData): mixed
    {
        $xsl = new DOMDocument();
        if (!empty($travelOrder->tpl_body)) {
            $xsl->loadXml($travelOrder->tpl_body->body, LIBXML_NOCDATA);
        } else {
            $xsltTpl = Plugin::path('Documents') . DS . 'webroot' . DS . 'doc_travel_order.xslt';
            $xsl->load($xsltTpl, LIBXML_NOCDATA);
        }

        $xslt = new XSLTProcessor();
        $xslt->importStylesheet($xsl);

        $xml = new DOMDocument();
        $xml->loadXML($xmlData);

        $result = $xslt->transformToXml($xml);

        $event = new Event('Documents.TravelOrders.Export.Html', $travelOrder, [$result]);
        EventManager::instance()->dispatch($event);
        $eventResult = $event->getResult();
        if (!empty($eventResult)) {
            $result = $eventResult;
        }

        return $result;
    }

    /**
     * Wraps double line-breaks in paragraph tags, single breaks as <br />.
     *
     * @param string $pee The text to format.
     * @param int|bool $br Whether to convert remaining line-breaks to <br />.
     * @return string Formatted text.
     */
    private function _autop(string $pee, int|bool $br = 1): string
    {
        if (trim($pee) === '') {
            return '';
        }
        $pee = $pee . "\n";
        $pee = (string)preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form' .
            '|map|area|blockquote|address|math|style|input|p|h[1-6]|hr)';
        $pee = (string)preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = (string)preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = (string)str_replace(["\r\n", "\r"], "\n", $pee);
        $pee = (string)preg_replace("/\n\n+/", "\n\n", $pee);
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        if (is_array($pees)) {
            foreach ($pees as $tinkle) {
                $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
            }
        }
        $pee = (string)preg_replace('|<p>\s*</p>|', '', $pee);
        $pee = (string)preg_replace('!<p>([^<]+)</(div|address|form)>!', '<p>$1</p></$2>', $pee);
        $pee = (string)preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee);
        $pee = (string)preg_replace('|<p>(<li.+?)</p>|', '$1', $pee);
        $pee = (string)preg_replace('|<p><blockquote([^>]*)>|i', '<blockquote$1><p>', $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = (string)preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', '$1', $pee);
        $pee = (string)preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee);
        if ($br) {
            $pee = (string)preg_replace_callback(
                '/<(script|style).*?<\/\\1>/s',
                fn($matches) => str_replace("\n", '<PreserveNewline />', $matches[0]),
                $pee,
            );
            $pee = (string)preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);
            $pee = str_replace('<PreserveNewline />', "\n", $pee);
        }
        $pee = (string)preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $pee);
        $pee = (string)preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        $pee = (string)preg_replace("|\n</p>$|", '</p>', $pee);

        return $pee;
    }
}
