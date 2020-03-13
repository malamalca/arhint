<?php
declare(strict_types=1);

namespace App\View;

use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/4/en/views.html#the-app-view
 */
class AppView extends View
{
    /**
     * @var \App\Model\Entity\User $currentUser
     */
    protected $currentUser = null;

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance.
     * @param \Cake\Http\Response|null $response Response instance.
     * @param \Cake\Event\EventManager|null $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $currentUser = $request->getAttribute('identity');
        if (!empty($currentUser)) {
            $this->currentUser = $currentUser;
        }

        parent::__construct($request, $response, $eventManager, $viewOptions);
    }

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->loadHelper('Arhint');

        $this->loadHelper('Lil.Lil', [
            'templates' => 'templates',
        ]);

        $this->loadHelper('Form', [
            'templates' => 'templates',
                'widgets' => [
                    'lil-decimal' => ['Lil.LilDecimal', '_view'],
                    'lil-date' => ['Lil.LilDate', '_view'],
                    'duration' => ['Duration', '_view'],
                ],
        ]);
    }

    /**
     * Returns current user.
     *
     * @return \App\Model\Entity\User|null
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
}
