<?php
namespace LaravelNeuro\Prompts;

use Illuminate\Support\Collection;
use LaravelNeuro\Contracts\CorporatePromptContract;

class BasicPrompt extends Collection implements CorporatePromptContract {

    public function promptEncode()
    {
        $promptString = $this->encoder();
        return $promptString;
    }

    public static function promptDecode(string $promptString)
    {
        $prompt = new static();
        $prompt->decoder($promptString);
        return $prompt;
    }

    public function setPrompt(string $prompt)
    {
        $this->set('prompt', $prompt);
        return $this;
    }

    public function encoder()
    {
        if(!$this->has('prompt')) $this->set('prompt', '');
        return $this->get('prompt');
    }

    public function decoder(string $promptString)
    {
        $this->set('prompt', $promptString);
    }

}