<?php
namespace LaravelNeuro\Networking;

use LaravelNeuro\Enums\TuringMode;
use LaravelNeuro\Enums\TuringMove;

/**
 * Represents the "head" of a Turing machine, which is responsible for reading
 * and writing data on the tape (stored via NetworkState entries) and controlling
 * the flow of execution by managing the current mode and head position.
 *
 * @package LaravelNeuro
 */
class TuringHead {

    /**
     * The current mode of the head (e.g., CONTINUE, STUCK, etc.).
     *
     * @var TuringMode
     */
    private TuringMode $mode = TuringMode::CONTINUE;

    /**
     * The next state/move directive for the head.
     * Can be an instance of TuringMove or an integer representing a position.
     *
     * @var TuringMove|int
     */
    private $nextState = TuringMove::NEXT;

    /**
     * The current data stored by the head.
     *
     * @var string
     */
    private string $data;

    /**
     * The current position of the head on the tape.
     *
     * @var int
     */
    private int $headPosition = 0;

    /**
     * Holds any error message encountered by the head.
     *
     * @var string
     */
    public string $error;

    /**
     * Sets the current mode of the head.
     *
     * @param TuringMode $mode The new mode.
     * @return TuringHead Returns the current instance for chaining.
     */
    public function setMode(TuringMode $mode) : TuringHead
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Sets the next state/move directive for the head.
     *
     * The value must be either an instance of TuringMove or an integer.
     *
     * @param TuringMove|int $nextState The next state directive.
     * @return TuringHead Returns the current instance for chaining.
     * @throws \Exception If the provided value is not a TuringMove instance or an integer.
     */
    public function setNext($nextState) : TuringHead
    {
        if (!($nextState instanceof TuringMove) && !is_int($nextState)) {
            throw new \Exception('An invalid target state was declared using TuringHead->setNext.');
        }
        $this->nextState = $nextState;
        return $this;
    }

    /**
     * Sets the data stored by the head.
     *
     * @param string $data The data to set.
     * @return TuringHead Returns the current instance for chaining.
     */
    public function setData(string $data) : TuringHead
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Sets the head's position on the tape.
     *
     * @param int $headPosition The position index.
     * @return TuringHead Returns the current instance for chaining.
     */
    public function setPosition(int $headPosition) : TuringHead
    {
        $this->headPosition = $headPosition;
        return $this;
    }

    /**
     * Retrieves the current position of the head.
     *
     * @return int The head position.
     */
    public function getPosition() : int
    {
        return $this->headPosition;
    }

    /**
     * Retrieves the current mode of the head.
     *
     * @return TuringMode The current mode.
     */
    public function getMode() : TuringMode
    {
        return $this->mode;
    }

    /**
     * Retrieves the next state/move directive.
     *
     * @return TuringMove|int The next state or move.
     */
    public function getNext()
    {
        return $this->nextState;
    }

    /**
     * Retrieves the data stored by the head.
     *
     * @return string The stored data.
     */
    public function getData() : string
    {
        return $this->data;
    }
    
}

/**
* Register TuringStrip as an alias for TuringHead for backwards compatibility
*/
if (!class_exists('LaravelNeuro\Networking\TuringStrip')) {
    class_alias(__NAMESPACE__ . '\TuringHead', __NAMESPACE__ . '\TuringStrip');
}