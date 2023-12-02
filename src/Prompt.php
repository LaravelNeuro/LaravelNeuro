<?php
namespace Kbirenheide\L3MA;
use Illuminate\Support\Collection;

class Prompt extends Collection {

    public function pushPurpose($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException("The value for pushPurpose must be a string.");
        }

            $this->push([ "type" => "purpose",
                                      "block" => $string]);

        return $this;
    }
    public function pushSystem($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException("The value for pushSystem must be a string.");
        }

            $this->push([ "type" => "system",
                                      "block" => $string]);
                                      
        return $this;
    }
    public function pushUser($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException("The value for pushUser must be a string.");
        }

            $this->push([ "type" => "user",
                                      "block" => $string]);
                                      
        return $this;
    }

}