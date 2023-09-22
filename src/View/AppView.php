<?php
declare(strict_types=1);

namespace App\View;

use App\Model\Entity\User;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;
use Exception;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/4/en/views.html#the-app-view
 * @property \Lil\View\Helper\LilHelper $Lil
 */
class AppView extends View
{
    /**
     * @var \App\Model\Entity\User|null $currentUser
     */
    protected ?User $currentUser = null;

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance.
     * @param \Cake\Http\Response|null $response Response instance.
     * @param \Cake\Event\EventManager|null $eventManager Event manager instance.
     * @param array<string, mixed> $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $currentUser = $request?->getAttribute('identity');
        if ($currentUser) {
            $this->currentUser = $currentUser->getOriginalData();
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
     * Returns is user is logged on.
     *
     * @return bool
     */
    public function hasCurrentUser(): bool
    {
        return !empty($this->currentUser);
    }

    /**
     * Returns current user.
     *
     * @return \App\Model\Entity\User
     */
    public function getCurrentUser(): User
    {
        if (!$this->currentUser) {
            throw new Exception('User does not exist.');
        }

        return $this->currentUser;
    }
}
