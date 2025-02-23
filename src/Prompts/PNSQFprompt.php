<?php
namespace LaravelNeuro\Prompts;

use LaravelNeuro\Prompts\BasicPrompt;

use LaravelNeuro\Enums\PNSQFquality;

class PNSQFprompt extends BasicPrompt {

    public function __construct()
    {
        $this->put('prompt', 'Create an image for me.');
        $this->put('number', 1);
        $this->put('size', '1024x1024');
        $this->put('quality', PNSQFquality::STANDARD->value);
        $this->put('response_format', 'url');
    }

    public function setPrompt(string $string)
    {
            $this->put("prompt", $string);
            
        return $this;
    }
    public function setNumber(int $number)
    {
            $this->put("number", $number);
                                      
        return $this;
    }
    public function setSize(int $x, int $y = -1)
    {
        if($y === -1) $y = $x;

            $this->put("size", $x."x".$y);
                                      
        return $this;
    }
    public function setQuality(PNSQFquality $enum = PNSQFquality::STANDARD)
    {
            $this->put("quality", $enum->value);
                                      
        return $this;
    }
    public function setFormat(string $string)
    {
            $this->put("response_format", $string);
                                      
        return $this;
    }

    public function getPrompt()
    {
        return $this->get('prompt');
    }

    public function getNumber()
    {
        return $this->get('number');
    }

    public function getSize()
    {
        return $this->get('size');
    }

    public function getQuality()
    {
        return $this->get('quality');
    }

    public function getFormat()
    {
        return $this->get('response_format');
    }

    public function encoder() : string
    {
        $promptText = $this->getPrompt();
        $number = $this->getNumber();
        $size = $this->getSize();
        $quality = $this->getQuality();
        $format = $this->getFormat();

        $nsqfString = "{{NSQF:{$number},{$size},{$quality},{$format}}}";

        $encodedPromptString = $promptText . $nsqfString;

        $encodedPrompt = ['prompt' => $encodedPromptString];

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    public function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        preg_match('/{{NSQF:(.*?)}}/', $promptData->prompt, $entities);

            $p = $promptData->prompt;
            $n = 1;
            $s = [1024, 1024];
            $q = PNSQFquality::STANDARD;
            $f = 'url';

        if(count($entities) > 0)
        {
            $data = $entities[1];

            $NSQF = explode(',', trim($data));
            $p = preg_replace('/{{NSQF:.*?}}/', '', $promptData->prompt);
            $n = $NSQF[0] ?? 1;
            $s = explode('x', ($NSQF[1] ?? "1024x1024")) ?? [1024, 1024];
            $q = PNSQFquality::tryFrom($NSQF[2]) ?? PNSQFquality::STANDARD;
            $f = $NSQF[3] ?? 'url';
        }

        $this->setPrompt($p);
        $this->setNumber($n);
        $this->setSize(...$s);
        $this->setQuality($q);
        $this->setFormat($f);
    }

}