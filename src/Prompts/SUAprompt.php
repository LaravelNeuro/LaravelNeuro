<?php
namespace LaravelNeuro\LaravelNeuro\Prompts;

use LaravelNeuro\LaravelNeuro\Prompts\BasicPrompt;

class SUAprompt extends BasicPrompt {

    public function pushSystem(string $string)
    {
            $this->push((object)[ "type" => "role",
                                      "block" => $string]);

        return $this;
    }
    public function pushAgent(string $string)
    {
            $this->push((object)[ "type" => "agent",
                                      "block" => $string]);
                                      
        return $this;
    }
    public function pushUser(string $string)
    {
            $this->push((object)[ "type" => "user",
                                      "block" => $string]);
                                      
        return $this;
    }

    protected function encoder()
    {
        $encodedPrompt = [];

        $encodedPrompt["role"] = '';
        $encodedPrompt["completion"] = [];
        $encodedPrompt["prompt"] = '';

        $this->each(function($item, $key) use (&$encodedPrompt) {
            if($key == ($this->count() - 1))
            {
                $encodedPrompt["prompt"] = $item->block;
            }
            elseif($item->type == "role")
            {
                $encodedPrompt["role"] = $item->block;
            }
            else
            {
                $encodedPrompt["completion"][] = $item->block;
            }

            }
        );

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        if(isset($promptData->role))
            {
                $this->pushSystem($promptData->role);
            }

            if(isset($promptData->completion))
            {
                foreach($promptData->completion as $key => $message)
                {
                    if($key % 2 == 0 || $key == 0)
                    {
                        $this->pushUser($message);
                    }
                    else
                    {
                        $this->pushAgent($message);
                    }
                }
            }

            if(isset($promptData->prompt))
            {
                $this->pushUser($promptData->prompt); 
            }
    }

}