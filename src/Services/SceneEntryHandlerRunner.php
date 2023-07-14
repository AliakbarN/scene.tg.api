<?php

namespace SceneApi\Services;

use SceneApi\SceneManager;
use Exception;
use SergiX44\Nutgram\Nutgram;

trait SceneEntryHandlerRunner
{
    protected array $availableEnterConditions = [
        'command',
        'text'
    ];

    protected function enter(Nutgram $bot) :void
    {
        $this->onEnter($bot);

        $this->manager->addUser($bot->userId(), $this->name, false);
    }

    protected function onCommand(string $command) :void
    {
        $this->bot->onCommand($command, function (Nutgram $bot)
        {
            $this->enter($bot);
        });
    }

    protected function onText(string $text) :void
    {
        $this->bot->onText($text, function (Nutgram $bot)
        {
            $this->enter($bot);
        });
    }

    /**
     * @throws Exception
     */
    public function runEntryHandler() :void
    {
        $this->checkEnterCondition();
        [$handler, $condition] = explode('=', $this->enterCondition);
        $this->handlerDistributor($condition, $handler);
    }

    protected function handlerDistributor(string $condition, string $handler) :void
    {
        if ($handler === 'command') {
            $this->onCommand($condition);
        } else if ($handler === 'text') {
            $this->onText($condition);
        }
    }

    protected function checkEnterCondition() :void
    {
        $flag = false;
        foreach ($this->availableEnterConditions as $condition)
        {
            if (explode('=', $this->enterCondition)[0] === $condition) {
                $flag = true;
            }
        }

        if (!$flag) {
            throw new Exception('The entryCondition [' . $this->enterCondition . '] is incorrect');
        }
    }
}
