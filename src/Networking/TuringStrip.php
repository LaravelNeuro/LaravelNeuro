<?php
namespace LaravelNeuro\LaravelNeuro\Networking;

use LaravelNeuro\LaravelNeuro\Enums\TuringMode;
use LaravelNeuro\LaravelNeuro\Enums\TuringMove;

class TuringStrip {

    private TuringMode $mode = TuringMode::CONTINUE;
    private $nextState = TuringMove::NEXT;
    private string $data;
    private int $headPosition = 0;
    public string $error;

    public function setMode(TuringMode $mode) : TuringStrip
    {
        $this->mode = $mode;
        return $this;
    }

    public function setNext($nextState) : TuringStrip
    {
        if(!($nextState instanceof TuringMove) && !is_int($nextState)) throw new \Exception('An invalid target state was declared using TuringStrip->setNext.'); 
        else $this->nextState = $nextState;
        return $this;
    }

    public function setData(string $data) : TuringStrip
    {
        $this->data = $data;
        return $this;
    }

    public function setPosition($headPosition)
    {
        $this->headPosition = $headPosition;

        return $this;
    }

    public function getPosition()
    {
        return $this->headPosition;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getNext()
    {
        return $this->nextState;
    }

    public function getData()
    {
        return $this->data;
    }

}