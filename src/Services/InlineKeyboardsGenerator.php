<?php

namespace SceneApi\Services;

use Exception;
use Random\Randomizer;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

trait InlineKeyboardsGenerator
{
    private Randomizer $randomizer;

    private function generateInlineKeyboard() : InlineKeyboardMarkup
    {
        $this->checkInlineKeyboards();

        $inlineKeyboard = InlineKeyboardMarkup::make();

        foreach ($this->inlineKeyboard as $item)
        {
            $buttons = [];

            foreach ($item as $button => $id)
            {
                $buttons[] = InlineKeyboardButton::make($button, callback_data: $id);
            }

            $inlineKeyboard->addRow(...$buttons);
        }

        return $inlineKeyboard;
    }

    private function generateId() :void
    {
        $updatedInlineKeyboard = [];
        foreach ($this->inlineKeyboard as $key => $value)
        {
            foreach ($value as $key2 => $value2)
            {
                $updatedInlineKeyboard[$key][$value2] = $this->getRandomString();
            }
        }

        $this->inlineKeyboard = $updatedInlineKeyboard;
    }

    private function getRandomString() :string
    {
        $randomBytes = $this->randomizer->getBytes(8);
        return bin2hex($randomBytes);
    }

    private function checkInlineKeyboards() :void
    {
        foreach ($this->inlineKeyboard as $key => $value)
        {
            if (!is_array($value)) {
                throw new Exception('The inlineKeyboards array structure is incorrect in class - ' . get_class($this) . "\n Example: [['name'], ['age', 'lastName']]");
            }

            foreach ($value as $key2 => $value2)
            {
                if (!is_string($value2)) {
                    throw new Exception('The inlineKeyboards array structure is incorrect in class - ' . get_class($this) . "\n Example: [['name'], ['age', 'lastName']]");
                }
            }
        }
    }
}