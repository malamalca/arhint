<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Response;
use Cake\Utility\Hash;
use DirectoryIterator;
use Lil\Lib\LilPdfFactory;

class ArhintReport
{
    /**
     * @var string $ext
     */
    private $ext = null;
    /**
     * @var \Cake\View\View $view
     */
    private $view = null;
    /**
     * @var array $pdfOptions
     */
    private $pdfOptions = null;
    /**
     * @var string $lastError
     */
    private $lastError = null;

    /**
     * Constructor
     *
     * @param string $template Template file with dot notation.
     * @param mixed $request Request object.
     * @param array $pdfOptions Options that are passed to pdf engine.
     * @return void
     */
    public function __construct($template, $request, $pdfOptions = [])
    {
        $this->pdfOptions = $pdfOptions;

        $viewClass = 'App\View\AppView';
        $viewOptions = [];

        //dd($request->getParam('plugin'));

        $this->view = new $viewClass($request, null, EventManager::instance(), []);
        $this->view->setSubDir('');
        $this->view->setTemplatePath('report');
        $this->view->enableAutoLayout(false);

        $templateName = strtr($template, '.', DS);
        $plugin = $request->getParam('plugin');
        if (!empty($plugin)) {
            $templateName = $plugin . '.' . $templateName;
        }
        $this->view->setTemplate($templateName);

        $this->view->loadHelper('Lil.Lil');
    }

    /**
     * Set variables to view
     *
     * @param mixed $name Param name
     * @param mixed $arguments Set arguments
     * @return mixed
     */
    public function set($name, $arguments = null)
    {
        $this->view->set($name, $arguments);
    }

    /**
     * Render template
     *
     * @return string
     */
    public function render()
    {
        $outputHtml = $this->view->render();

        return $outputHtml;
    }

    /**
     * export method
     *
     * @return string
     */
    public function export()
    {
        $result = null;

        $pdfEngine = Configure::read('pdfEngine', 'TCPDF');
        $pdfOptions = Configure::read($pdfEngine, []);

        $pdf = LilPdfFactory::create($pdfEngine, Hash::merge((array)$pdfOptions, $this->pdfOptions));

        if (!empty($invoice->tpl_header)) {
            $pdf->setHeaderHtml($this->_autop($invoice->tpl_header->body));
        }
        if (!empty($invoice->tpl_footer)) {
            $pdf->setFooterHtml($this->_autop($invoice->tpl_footer->body));
        }

        $outputHtml = $this->view->render();
        if (!empty($outputHtml)) {
            // output body
            $rendered = explode('<!-- NEW PAGE -->', $outputHtml);

            $cssFilename = constant('WWW_ROOT') . 'css' . DS . 'pdf.css';
            foreach ($rendered as $page) {
                $page = '<style>' . file_get_contents($cssFilename) . '</style>' . $page;
                $pdf->newPage($page, []);
            }
        }

        // unlink old files
        $this->clearReportCache();

        $tmpFilename = uniqid('xml2pdf') . '.pdf';
        if (!$pdf->saveAs(constant('TMP') . $tmpFilename)) {
            $this->lastError = $pdf->getError();
            throw new InternalErrorException($this->lastError);
        }

        return $tmpFilename;
    }

    /**
     * Deletes all files from TMP folder with 'xml2pdf' prefix and are older than 1 day
     *
     * @return void
     */
    private function clearReportCache()
    {
        foreach (new DirectoryIterator(constant('TMP')) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $fileAge = time() - $fileInfo->getCTime();
            $maxAge = 24 * 60 * 60;
            if ($fileInfo->isFile() && ($fileAge >= $maxAge) && (substr($fileInfo->getBasename(), 0, 7) == 'xml2pdf')) {
                unlink((string)$fileInfo->getRealPath());
            }
        }
    }

    /**
     * Returns Cake response object
     *
     * @param string $data Result of Export funtion.
     * @param array $options Export options
     * @return \Cake\Http\Response
     */
    public function response($data, $options = [])
    {
        $defaults = ['download' => false, 'filename' => 'documents'];
        $options = array_merge($defaults, $options);

        $result = new Response();
        $result = $result->withStringBody($data);
        $result = $result->withType('pdf');

        if ($options['download']) {
            $result = $result->withDownload($options['filename'] . '.' . $this->ext);
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
     *
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
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|' .
            'form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr)';
        $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = str_replace(["\r\n", "\r"], "\n", $pee); // cross-platform newlines
        if (strpos($pee, '<object') !== false) {
            $pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
            $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
        }
        $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
        // make paragraphs, including one at the end
        $pees = (array)preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim((string)$tinkle, "\n") . "</p>\n";
        }
        $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
        $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
        if ($br) {
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', function ($matches) {
                return str_replace("\n", "<PreserveNewline />", $matches[0]);
            }, $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<PreserveNewline />', "\n", $pee);
        }
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        return $pee;
    }
}
