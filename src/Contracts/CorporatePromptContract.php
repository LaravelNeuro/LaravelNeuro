<?php

namespace LaravelNeuro\Contracts;

Interface CorporatePromptContract
{
    public function promptEncode();

    public static function promptDecode(string $promptString);
}