<?php

namespace LaravelNeuro\Contracts\Networking;

Interface CorporatePrompt
{
    public function promptEncode();

    public static function promptDecode(string $promptString);
}