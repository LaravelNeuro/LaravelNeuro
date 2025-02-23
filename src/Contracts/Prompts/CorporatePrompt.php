<?php

namespace LaravelNeuro\Contracts\Prompts;

Interface CorporatePrompt
{
    public function promptEncode();

    public static function promptDecode(string $promptString);
}