<?php

namespace SceneApi;

use SceneApi\BaseScene;
use SceneApi\Services\BaseMiddleware;
use SceneApi\Services\GetOption;
use SceneApi\Services\GetScenes;
use SceneApi\Services\MiddlewaresManager;
use SceneApi\Services\ODT\UserState;
use Exception;
use SergiX44\Nutgram\Nutgram;

class SceneManager
{

    use MiddlewaresManager;


    protected GetOption $option;

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
    protected array $users;

    protected Nutgram $bot;

    public function __construct(Nutgram $bot, ?array $scenes = null, array $options = null)
    {
        $this->bot = $bot;

        $this->option = new GetOption($options);

        if ($scenes === null) {
            if (count($this->scenes) === 0) {
                if ($this->option->get('sceneNameSpace') & $this->option->get('scenesDirPath')) {
                    $this->scenes = GetScenes::getSceneClasses($this->option->get('scenesDirPath'), $this->option->get('sceneNameSpace'));
                }
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
        if ($this->checkClasses()) {
            return;
        }

        foreach ($this->scenes as $key => $scene)
        {
            $this->initiatedScenes[] = new $scene($this->bot, $this);

            $this->checkMiddlewares($this->initiatedScenes[$key]);

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

            if (!array_key_exists($userId, $this->users) & !$this->users[$userId]->isActive) {
                return;
            }

            $scene = $this->findSceneByName($this->users[$userId]->sceneName);

            if ($scene === null) {
                $this->log('Something went wrong. /n/r' . $scene .' = null. userId = '. $userId, false);
            }

            if (!$this->users[$userId]->isActive) {
                return;
            }

            if ($this->users[$userId]->isEnter) {
                $scene->onEnter($bot);
                $this->users[$userId]->isEnter = false;
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
    protected function checkClasses() :bool
    {
        foreach ($this->scenes as $scene)
        {
            if (!is_a($scene, BaseScene::class, true)) {
                $this->log('The class [' . $scene .'] is not extended by BaseScene');
            }
        }

        if (count($this->scenes) === 0) {
            return false;
        }

        return true;
    }

    protected function findSceneByName(string $sceneName) :BaseScene|null
    {
        foreach ($this->initiatedScenes as $scene)
        {
            if ($scene->name === null) {
                $scene->name = $this->generateSceneName($scene::class);
            }

            if ($scene->name === $sceneName) {
                return $scene;
            }
        }

        return null;
    }

    protected function generateSceneName(string $sceneClassName) :string
    {
        $sceneClassName[0] = strtolower($sceneClassName[0]);
        return $sceneClassName;
    }

    public function addUser(int $userId, string $sceneName, bool $isEnter) :void
    {
        $this->users[$userId] = new UserState($sceneName, true, $isEnter);
    }

    public function deleteUser(int $userId) :void
    {
        unset($this->users[$userId]);
    }

    public function changeUserState(int $userId, bool $state) :void
    {
        $this->users[$userId]->isActive = $state;
    }

    public function next(Nutgram $bot, int $userId, string $sceneName) :void
    {
        $this->users[$userId]->sceneName = $sceneName;
        $this->users[$userId]->isEnter = false;

        $scene = $this->findSceneByName($sceneName);

        if ($scene === null) {
            $this->log('Something went wrong', false);
        }

        $scene->onEnter($bot);
    }

    public function setData(array $data, int $userId) :void
    {
        foreach ($data as $key => $value)
        {
            $this->users[$userId]->globalData[$key] = $value;
        }
    }

    public function getData(string $key, int $userId) :mixed
    {
        if (!array_key_exists($key, $this->users[$userId]->globalData)) {
            $this->log('The data [' . $key . '] has nor been added yet', true);
            return null;
        }
        return $this->users[$userId]->globalData[$key];
    }

    function log(string $message, bool $softWarning = null) :void
    {
        if ($softWarning !== null) {
            if ($softWarning) {
                var_dump($message);
            } else {
                throw new Exception($message);
            }

            return;
        }

        if (!$this->option->get('soft-warning')) {
            throw new Exception($message);
        } else {
            var_dump($message);
        }
    }
}
