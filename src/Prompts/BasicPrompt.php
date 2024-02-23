<?php
namespace LaravelNeuro\LaravelNeuro\Prompts;

use Illuminate\Support\Collection;

class BasicPrompt extends Collection {

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

    protected function encoder()
    {
        if(!$this->has('prompt')) $this->set('prompt', '');
        return $this->get('prompt');
    }

    protected function decoder(string $promptString)
    {
        $this->set('prompt', $promptString);
    }

}