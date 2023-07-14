<?php

namespace SceneApi;

use SceneApi\Services\SceneEntryHandlerRunner;
use SergiX44\Nutgram\Nutgram;

abstract class BaseScene
{
    use SceneEntryHandlerRunner;


    private Nutgram $bot;

    private SceneManager $manager;

    /**
     * @var string|null
     */
    public null|string $name = null;

    /**
     * @var array
     * Middlewares for only this class
     */
    public array $middlewares = [

    ];

    public bool $wasMiddlewaresRun = false;

    /**
     * @var string
     * Conditions for entering into scene
     * Available options: command, text
     *
     * @example 'command=start'
     */
    public string $enterCondition;


    public function __construct(Nutgram $bot, SceneManager $manager)
    {
        $this->bot = $bot;
        $this->manager = $manager;
    }

    /**
     * @return void
     * Break down Scene
     */
    public function break(int $userId) :void
    {
        $this->manager->changeUserState($userId, false);
        $this->wasMiddlewaresRun = false;
    }

    /**
     * @return void
     * Jump to next scene
     * @throws \Exception
     */
    public function next(Nutgram$bot, int $userId, string $sceneName) :void
    {
        $this->manager->next($bot, $userId, $sceneName);
        $this->manager->changeUserState($userId, true);
        $this->wasMiddlewaresRun = false;
    }

    /**
     * @return void
     * Works out when condition works
     */
    public function onEnter(Nutgram $bot) : void {}

    /**
     * @param Nutgram $bot
     * @return void
     *
     * Works out when user sends a request
     */
    public function onQuery(Nutgram $bot) :void {}

    /**
     * @param Nutgram $bot
     * @return bool
     */
    public function isSuccess(Nutgram $bot) :bool {
        return true;
    }

    /**
     * @param Nutgram $bot
     * @return void
     *
     * Works out when a failure is handled
     */
    public function onFail(Nutgram $bot) :void {}
}