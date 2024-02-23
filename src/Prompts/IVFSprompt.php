<?php
namespace LaravelNeuro\LaravelNeuro\Prompts;

use LaravelNeuro\LaravelNeuro\Prompts\BasicPrompt;

class IVFSprompt extends BasicPrompt {

    public function __construct()
    {
        $this->put('input', 'Say this sentence out loud.');
        $this->put('voice', null);
        $this->put('format', 'mp3');
        $this->put('settings', []);
    }

    public function setInput(string $string)
    {
            $this->put("input", $string);
            
        return $this;
    }
    public function setVoice(string $string)
    {
            $this->put("voice", $string);
                                      
        return $this;
    }
    public function setFormat(string $string)
    {
            $this->put("format", $string);
                                      
        return $this;
    }

    public function settings(array $keyValueArray)
    {
            $this->put("settings", $keyValueArray);
                                      
        return $this;
    }

    public function getInput()
    {
        return $this->get('input');
    }

    public function getVoice()
    {
        return $this->get('voice');
    }

    public function getFormat()
    {
        return $this->get('format');
    }

    public function getSettings()
    {
        return $this->get('settings');
    }

    protected function encoder() : string
    {
        $input = $this->getInput();
        $voice = $this->getVoice();
        $format = $this->getFormat();
        $settings = $this->getSettings();
        $stringifySettings = '';

        foreach ($settings as $key => $setting) 
        {
            $stringifySettings .= $stringifySettings ? ",{$key}|{$setting}" : "{$key}|{$setting}";
        }

        $vfsString = "{{VFS:{$voice},{$format},{$stringifySettings}}}";

        $encodedPromptString = $input . $vfsString;

        $encodedPrompt = ['prompt' => $encodedPromptString];

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        preg_match('/{{VFS:(.*?)}}/', $promptData->prompt, $entities);

        $i = $promptData->prompt;
        $v = '';
        $f = 'mp3';
        $s = [];

        if(count($entities) > 0)
        {
            $data = $entities[1];
            $VFS = explode(',', $data);
            $i = preg_replace('/{{VFS:.*?}}/', '', $promptData->prompt);
            $v = $VFS[0];
            $f = $VFS[1] ?? 'mp3';
            foreach($VFS as $key=>$value)
            {
                if($key > 1)
                {
                    $key_value = explode('|', $value);
                    $s[$key_value[0]] = $key_value[1]; 
                }
            }
        }

        $this->setInput($i);
        $this->setVoice($v);
        $this->setFormat($f);
        $this->settings($s);
    }

}