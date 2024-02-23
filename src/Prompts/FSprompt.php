<?php
namespace LaravelNeuro\LaravelNeuro\Prompts;

use LaravelNeuro\LaravelNeuro\Prompts\BasicPrompt;

class FSprompt extends BasicPrompt {

    public function setFile($string)
    {
            $this->put('file', $string);
            
        return $this;
    }

    public function settings(array $keyValueArray)
    {
            $this->put('settings', $keyValueArray);
                                      
        return $this;
    }

    public function getFile()
    {
        return $this->get('file');
    }

    public function getSettings()
    {
        return $this->get('settings');
    }

    protected function encoder() : string
    {
        $file = $this->getFile();
        $settings = $this->getSettings();
        $stringifySettings = '';

        foreach ($settings as $key => $setting) 
        {
            $stringifySettings .= $stringifySettings ? ",{$key}|{$setting}" : "{$key}|{$setting}";
        }

        $fsString = "{{FS:{$file},{$stringifySettings}}}";

        $encodedPromptString = $fsString;

        $encodedPrompt = ['prompt' => $encodedPromptString];

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        preg_match('/{{FS:(.*?)}}/', $promptData->prompt, $entities);

        $f = '';
        $s = [];

        if(count($entities) > 0)
        {
            $f = '';
            $s = [];

            $data = $entities[1];
            $FS = explode(',', $data);
            $f = $FS[0] ?? '';
            foreach($FS as $key=>$value)
            {
                if($key > 0)
                {
                    $key_value = explode('|', $value);
                    $s[$key_value[0]] = $key_value[1]; 
                }
            }
        }
        else
        {
            $f = $promptData->prompt;
        }

        $this->setFile($f);
        $this->settings($s);
    }

}