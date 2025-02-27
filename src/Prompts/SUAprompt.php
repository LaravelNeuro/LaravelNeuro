<?php
namespace LaravelNeuro\Prompts;

use LaravelNeuro\Prompts\BasicPrompt;

/**
 * An extension of BasicPrompt designed for chat-completion pipelines.
 * SUAprompt (System, User, Agent) allows you to build prompts with distinct roles.
 * It provides methods to push system, agent, and user messages onto the prompt stack.
 *
 * @package LaravelNeuro
 */
class SUAprompt extends BasicPrompt {

    /**
     * Pushes a system message onto the prompt.
     *
     * The system message is flagged with the "role" type.
     *
     * @param string $string The system message text.
     * @return $this Returns the current instance for chaining.
     */
    public function pushSystem(string $string)
    {
        $this->push((object)[ 
            "type"  => "role",
            "block" => $string
        ]);
        return $this;
    }

    /**
     * Pushes an agent message onto the prompt.
     *
     * The agent message is flagged with the "agent" type.
     *
     * @param string $string The agent message text.
     * @return $this Returns the current instance for chaining.
     */
    public function pushAgent(string $string)
    {
        $this->push((object)[ 
            "type"  => "agent",
            "block" => $string
        ]);
        return $this;
    }

    /**
     * Pushes a user message onto the prompt.
     *
     * The user message is flagged with the "user" type.
     *
     * @param string $string The user message text.
     * @return $this Returns the current instance for chaining.
     */
    public function pushUser(string $string)
    {
        $this->push((object)[ 
            "type"  => "user",
            "block" => $string
        ]);
        return $this;
    }

    /**
     * Encodes the SUAprompt into a JSON string.
     *
     * This method iterates over the prompt collection and builds an associative array
     * with keys "role", "completion", and "prompt". The "role" key holds the system message,
     * "completion" collects alternating messages for user and agent, and "prompt" contains the final message.
     *
     * @return string The JSON-encoded representation of the prompt, formatted with JSON_PRETTY_PRINT.
     */
    protected function encoder() : string
    {
        $encodedPrompt = [];
        $encodedPrompt["role"] = '';
        $encodedPrompt["completion"] = [];
        $encodedPrompt["prompt"] = '';

        $this->each(function($item, $key) use (&$encodedPrompt) {
            // The final element is considered the final prompt
            if ($key == ($this->count() - 1)) {
                $encodedPrompt["prompt"] = $item->block;
            } elseif ($item->type == "role") {
                $encodedPrompt["role"] = $item->block;
            } else {
                $encodedPrompt["completion"][] = $item->block;
            }
        });

        return json_encode($encodedPrompt, JSON_PRETTY_PRINT);
    }

    /**
     * Decodes a JSON-encoded prompt string and reconstructs the SUAprompt.
     *
     * This method decodes the JSON string into its component parts. It pushes the system message,
     * iterates through the "completion" array to alternate between user and agent messages,
     * and finally pushes the final prompt as a user message.
     *
     * @param string $prompt The JSON-encoded prompt string.
     * @return void
     */
    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        if (isset($promptData->role)) {
            $this->pushSystem($promptData->role);
        }

        if (isset($promptData->completion)) {
            foreach ($promptData->completion as $key => $message) {
                // Alternate between user and agent based on the index
                if ($key % 2 == 0 || $key == 0) {
                    $this->pushUser($message);
                } else {
                    $this->pushAgent($message);
                }
            }
        }

        if (isset($promptData->prompt)) {
            $this->pushUser($promptData->prompt); 
        }
    }
}