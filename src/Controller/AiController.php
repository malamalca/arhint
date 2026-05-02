<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\AIAssistant;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * AI Controller
 */
class AiController extends AppController
{
    /**
     * Before filter callback to allow unauthenticated access to chat and clearHistory actions.
     *
     * @param \Cake\Event\EventInterface $event The event object.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->FormProtection->setConfig('unlockedActions', ['chat', 'clearHistory']);
    }

    /**
     * Chat action to handle AI assistant interactions.
     * Accepts user input, gets a response from the AIAssistant, and returns it as JSON.
     */
    public function chat(): Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod('post');

        $userInput = trim((string)$this->request->getData('message'));
        if ($userInput === '') {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode(['error' => 'Empty message', 'response' => null]));
        }

        $session = $this->request->getSession();
        $history = $session->read('AIAssistant.history') ?? [];

        $assistant = new AIAssistant($this->getCurrentUser());
        $assistant->setHistory($history);

        $response = $assistant->getResponse($userInput);

        $session->write('AIAssistant.history', $assistant->getHistory());

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $responseHtml = (string)$converter->convert($response);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'response' => $responseHtml,
                'redirect' => $assistant->getRedirectUrl(),
            ]));
    }

    /**
     * Clears the AI conversation history for the current user.
     */
    public function clearHistory(): Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod('post');

        $this->request->getSession()->delete('AIAssistant.history');

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['cleared' => true]));
    }
}
