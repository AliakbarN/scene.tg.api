<?php

namespace SceneApi\Services;

use Random\Randomizer;
use SceneApi\BaseScene;
use SceneApi\SceneManager;
use SergiX44\Nutgram\Nutgram;

abstract class BaseInlineKeyboardsScene extends BaseScene
{

    use InlineKeyboardsGenerator;


    public ?string $name = null;

    protected ?string $defaultText = null;

    /**
     * @var array
     * @example [['name'], ['age']];
     */
    protected array $inlineKeyboard = [];

    /**
     * @var array
     * @example ['name' => handlerFunction, 'age' => anotherHandlerFunction];
     *
     * Handlers are functions of a current Scene class
     */
    private array $handlers = [];


    public function __construct(Nutgram $bot, SceneManager $manager)
    {
        parent::__construct($bot, $manager);
        $this->boot();
        $this->randomizer = new Randomizer();
        $this->generateId();
    }

    public function onEnter(Nutgram $bot): void
    {
        $this->checkDefaultText();

        $bot->sendMessage(
            text: $this->defaultText,
            reply_markup: $this->generateInlineKeyboard()
        );
    }

    public function onQuery(Nutgram $bot): void
    {
        $id = $bot->callbackQuery()->data;
        $handlerFunction = $this->getHandlerFunction($id);

        try {
            $handlerFunction($bot);
        } catch (\Exception $exception) {
            throw new \Exception('Your something wend wrong with the handler - ' . $handlerFunction . ' (its access key should be "public")');
        }
    }

    public function onFail(Nutgram $bot): void
    {

    }

    abstract protected function boot(): void;

    public function isSuccess(Nutgram $bot): bool
    {
        $id = $bot->callbackQuery()->data;

        foreach ($this->inlineKeyboard as $item)
        {
            foreach ($item as $buttonName => $buttonId)
            {
                if ($id == $buttonId) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getHandlerFunction(string $needyId) :callable
    {
        foreach ($this->inlineKeyboard as $item)
        {
            foreach ($item as $button => $id)
            {
                if ($needyId === $id) {
                    foreach ($this->handlers as $handler)
                    {
                        if (is_array($handler['buttonName'])) {
                            if (in_array($button, $handler['buttonName'])) {
                                return $handler['handler'];
                            }
                        }
                        if ($handler['buttonName'] === $button) {
                            return $handler['handler'];
                        }
                    }

                    throw new \Exception('The button ' . $button . ' does not have its handler, register it in $handlers array');
                }
            }
        }

        throw new \Exception('A button does not have its handler, register it in $handlers array');
    }


    protected function registerHandlers(callable $handler, string|array $buttonName): void
    {
        $this->handlers[] = ['handler' => $handler, 'buttonName' => $buttonName];
    }

    private function checkDefaultText() :void
    {
        if ($this->defaultText === null) {
            throw new \Exception('The defaultText should be defined ');
        }
    }
}
