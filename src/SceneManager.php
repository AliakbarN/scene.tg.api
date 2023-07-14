<?php

namespace SceneApi;

use SceneApi\BaseScene;
use SceneApi\Services\BaseMiddleware;
use SceneApi\Services\GetScenes;
use SceneApi\Services\MiddlewaresManager;
use SceneApi\Services\ODT\UserState;
use Exception;
use SergiX44\Nutgram\Nutgram;

class SceneManager
{

    use MiddlewaresManager;


    /**
     * @var array
     * Scenes list
     */
    protected array $scenes = [

    ];

    /**
     * @var BaseScene[]
     */
    protected array $initiatedScenes;

    /**
     * @var array
     */
    protected static array $users;

    protected Nutgram $bot;

    public function __construct(Nutgram $bot, ?array $scenes = null)
    {
        $this->bot = $bot;

        if ($scenes === null) {
            if (count($this->scenes) === 0) {
                $this->scenes = GetScenes::getSceneClasses(__DIR__ . '/../', 'App/Services');
            }
        } else {
            $this->scenes = $scenes;
        }
    }

    /**
     * @throws Exception
     */
    public function process() :void
    {
        $this->checkClasses(false);

        foreach ($this->scenes as $key => $scene)
        {
            $this->initiatedScenes[] = new $scene($this->bot, $this);

            $this->checkMiddlewares($this->initiatedScenes[$key], false);

            $this->initiatedScenes[$key]->runEntryHandler();
        }

        $this->handle();
    }

    /**
     * @return void
     */
    protected function handle() :void
    {
        $this->bot->onMessage(function (Nutgram $bot)
        {
            $userId = $bot->userId();

            if (!array_key_exists($userId, self::$users) & !self::$users[$userId]->isActive) {
                return;
            }

            $scene = $this->findSceneByName(self::$users[$userId]->sceneName);

            if ($scene === null) {
                throw new Exception('Something went wrong. /n/r' . $scene .' = null. userId = '. $userId);
            }

            if (!self::$users[$userId]->isActive) {
                return;
            }

            if (self::$users[$userId]->isEnter) {
                $scene->onEnter($bot);
                self::$users[$userId]->isEnter = false;
                return;
            }

            if (!$scene->wasMiddlewaresRun) {
                $breakDownChain = $this->manageMiddlewares($bot, $this->initiateMiddlewares($scene->middlewares));
                $scene->wasMiddlewaresRun = true;
                if ($breakDownChain) {
                    return;
                }
            }

            if (!$scene->isSuccess($bot)) {
                $scene->onFail($bot);
            }

            $scene->onQuery($bot);
        });
    }

    /**
     * @throws Exception
     */
    protected function checkClasses(bool $softWarning = true) :void
    {
        foreach ($this->scenes as $scene)
        {
            if (!is_a($scene, BaseScene::class, true)) {
                if ($softWarning) {
                    var_dump('The class [' . $scene .'] is not extended by BaseScene');
                } else {
                    throw new Exception('The class [' . $scene .'] is not extended by BaseScene');
                }
            }
        }
    }

    protected function findSceneByName(string $sceneName) :BaseScene|null
    {
        foreach ($this->initiatedScenes as $scene)
        {
            if ($scene->name === $sceneName) {
                return $scene;
            }
        }

        return null;
    }

    public function addUser(int $userId, string $sceneName, bool $isEnter) :void
    {
        self::$users[$userId] = new UserState($sceneName, true, $isEnter);
    }

    public function deleteUser(int $userId) :void
    {
        unset(self::$users[$userId]);
    }

    public function changeUserState(int $userId, bool $state) :void
    {
        self::$users[$userId]->isActive = $state;
    }

    public function next(Nutgram $bot, int $userId, string $sceneName) :void
    {
        self::$users[$userId]->sceneName = $sceneName;
        self::$users[$userId]->isEnter = false;

        $scene = $this->findSceneByName($sceneName);

        if ($scene === null) {
            throw new Exception('Something went wrong');
        }

        $scene->onEnter($bot);
    }
}
