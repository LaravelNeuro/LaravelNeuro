<?php
namespace Kbirenheide\L3MA\Prompts\Ollama;

use Illuminate\Support\Collection;

class Prompt extends Collection {

    public function pushRole($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException("The value for pushPurpose must be a string.");
        }

            $this->push((object)[ "type" => "role",
                                      "block" => $string]);

        return $this;
    }
    public function pushSystem($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException("The value for pushSystem must be a string.");
        }

            $this->push((object)[ "type" => "system",
                                      "block" => $string]);
                                      
        return $this;
    }
    public function pushUser($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException("The value for pushUser must be a string.");
        }

            $this->push((object)[ "type" => "user",
                                      "block" => $string]);
                                      
        return $this;
    }

}