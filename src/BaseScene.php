<?php

namespace SceneApi;

use Exception;
use SceneApi\Services\IScene;
use SceneApi\Services\SceneEntryHandlerRunner;
use SergiX44\Nutgram\Nutgram;

abstract class BaseScene implements IScene
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
    public array $middlewares = [];

    public bool $isDurable = false;
    public bool $isTemporary = false;

    public bool $wasMiddlewaresRun = false;

    /**
     * @var string|null
     * Conditions for entering into scene
     * Available options: command, text
     *
     * @example 'command=start'
     *
     * command
     * text
     * callBackQuery
     */
    public ?string $enterCondition = null;


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
     * @throws Exception
     */
    public function next(Nutgram$bot, string $sceneName) :void
    {
        $this->manager->next($bot, $bot->userId(), $sceneName);
        $this->manager->changeUserState($bot->userId(), true);
        $this->wasMiddlewaresRun = false;
    }

    /**
     * @param Nutgram $bot
     * @return bool
     */
    public function isSuccess(Nutgram $bot) :bool {
        return true;
    }

    protected function setData(array $data, int $userId) :void
    {
        $this->manager->setData($data, $userId);
    }

    protected function getData(string $key, int $userId) :mixed
    {
        return $this->manager->getData($key, $userId);
    }
}
