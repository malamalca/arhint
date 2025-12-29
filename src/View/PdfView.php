<?php
declare(strict_types=1);

/**
 * PdfView Pdf view class
 *
 * PHP version 5.3
 *
 * @category Class
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
namespace App\View;

use App\Lib\LilPdfFactory;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * PdfView Pdf view class
 *
 * @category Class
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
class PdfView extends AppView
{
    /**
     * The name of the layout file
     *
     * @var string
     */
    public string $layout = 'pdf';
    /**
     * Pdf views are located in the 'pdf' sub directory for controllers' views.
     *
     * @var string
     */
    public string $subDir = 'pdf';
    /**
     * pdf Class
     *
     * @var mixed
     */
    protected mixed $pdf = null;
    /**
     * viewOptions Class
     *
     * @var array<string, mixed>
     */
    protected array $viewOptions = [];

    /**
     * Last error message
     *
     * @var string|null
     */
    protected ?string $lastError = null;

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance.
     * @param \Cake\Http\Response|null $response Response instance.
     * @param \Cake\Event\EventManager $eventManager EventManager instance.
     * @param array<string, mixed> $viewOptions  An array of view options
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManager $eventManager = null,
        array $viewOptions = [],
    ) {
        parent::__construct($request, $response, $eventManager, $viewOptions);

        $pdfEngine = Configure::read('Pdf.pdfEngine');
        $pdfEngineSettings = Configure::read('Pdf.' . $pdfEngine);
        $pdfOptions = Configure::read('Pdf.pdfOptions');

        $event = new Event('App.Pdf.init', $this, [
            'engine' => $pdfEngine,
            'settings' => $pdfEngineSettings,
            'options' => $pdfOptions,
        ]);
        EventManager::instance()->dispatch($event);

        $pdfEngine = $event->getData('engine');
        $pdfEngineSettings = $event->getData('settings');
        $pdfOptions = $event->getData('options');

        $this->viewOptions = array_merge($pdfOptions, (array)$viewOptions);

        $this->pdf = LilPdfFactory::create($pdfEngine, $pdfEngineSettings);
    }

    /**
     * Magic accessor for pdf.
     *
     * @param string $method Name of the method to execute.
     * @param array<string, mixed> $args Arguments for called method.
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        $callable = [$this->pdf, $method];
        if (is_callable($callable)) {
            return call_user_func_array($callable, $args);
        }

        return null;
    }

    /**
     * Render a PDF view.
     *
     * @param string|null $template The view being rendered.
     * @param string|null $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render(?string $template = null, string|false|null $layout = null): string
    {
        $data = parent::render($template, $layout);

        if (!empty($data)) {
            // output body
            $rendered = explode('<!-- NEW PAGE -->', $data);

            foreach ($rendered as $page) {
                $pageHtml = $this->viewOptions['pagePre'] . $page . $this->viewOptions['pagePost'];
                $this->pdf->newPage($pageHtml);
            }
        }
        $tmpFilename = TMP . uniqid('xml2pdf') . '.pdf';
        if (!$this->pdf->saveAs($tmpFilename)) {
            $this->lastError = $this->pdf->getError();

            return '';
        }
        if (!file_exists($tmpFilename)) {
            $this->lastError = 'PDF file doesn\'t exist.';

            return '';
        }
        $result = file_get_contents($tmpFilename);

        unlink($tmpFilename);

        return (string)$result;
    }
}
