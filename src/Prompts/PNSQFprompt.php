<?php
namespace LaravelNeuro\Prompts;

use LaravelNeuro\Prompts\BasicPrompt;
use LaravelNeuro\Enums\PNSQFquality;

/**
 * Class PNSQFprompt
 *
 * PNSQFprompt stands for "prompt, number, size, quality, format" and is specifically designed
 * for image generation pipelines. It extends BasicPrompt to provide methods for setting and
 * retrieving image generation parameters such as prompt text, the number of images, image size,
 * quality, and response format. The encoder method builds a custom NSQF block appended to the
 * prompt, and the decoder method extracts these parameters from a JSON-encoded string.
 *
 * @package LaravelNeuro
 */
class PNSQFprompt extends BasicPrompt {

    /**
     * PNSQFprompt constructor.
     *
     * Initializes the prompt with default image generation parameters:
     * - prompt: "Create an image for me."
     * - number: 1
     * - size: "1024x1024"
     * - quality: STANDARD (from PNSQFquality enum)
     * - response_format: "url"
     */
    public function __construct()
    {
        $this->put('prompt', 'Create an image for me.');
        $this->put('number', 1);
        $this->put('size', '1024x1024');
        $this->put('quality', PNSQFquality::STANDARD->value);
        $this->put('response_format', 'url');
    }

    /**
     * Sets the prompt text.
     *
     * @param string $string The prompt text.
     * @return $this Returns the current instance for method chaining.
     */
    public function setPrompt(string $string)
    {
        $this->put("prompt", $string);
        return $this;
    }

    /**
     * Sets the number of images to generate.
     *
     * @param int $number The number of images.
     * @return $this Returns the current instance for method chaining.
     */
    public function setNumber(int $number)
    {
        $this->put("number", $number);
        return $this;
    }

    /**
     * Sets the image size.
     *
     * Accepts width and height parameters. If the height is not provided or is -1,
     * the width is used for both dimensions.
     *
     * @param int $x The width of the image.
     * @param int $y The height of the image (optional, defaults to -1).
     * @return $this Returns the current instance for method chaining.
     */
    public function setSize(int $x, int $y = -1)
    {
        if ($y === -1) {
            $y = $x;
        }
        $this->put("size", $x . "x" . $y);
        return $this;
    }

    /**
     * Sets the quality of the generated image.
     *
     * @param PNSQFquality $enum The quality value, defaults to PNSQFquality::STANDARD.
     * @return $this Returns the current instance for method chaining.
     */
    public function setQuality(PNSQFquality $enum = PNSQFquality::STANDARD)
    {
        $this->put("quality", $enum->value);
        return $this;
    }

    /**
     * Sets the response format.
     *
     * @param string $string The response format (e.g., "url").
     * @return $this Returns the current instance for method chaining.
     */
    public function setFormat(string $string)
    {
        $this->put("response_format", $string);
        return $this;
    }

    /**
     * Retrieves the prompt text.
     *
     * @return mixed The prompt text.
     */
    public function getPrompt()
    {
        return $this->get('prompt');
    }

    /**
     * Retrieves the number of images to generate.
     *
     * @return mixed The number of images.
     */
    public function getNumber()
    {
        return $this->get('number');
    }

    /**
     * Retrieves the image size.
     *
     * @return mixed The image size as a string (e.g., "1024x1024").
     */
    public function getSize()
    {
        return $this->get('size');
    }

    /**
     * Retrieves the quality setting.
     *
     * @return mixed The quality value.
     */
    public function getQuality()
    {
        return $this->get('quality');
    }

    /**
     * Retrieves the response format.
     *
     * @return mixed The response format.
     */
    public function getFormat()
    {
        return $this->get('response_format');
    }

    /**
     * Encodes the prompt and image generation parameters into a JSON string.
     *
     * Constructs an NSQF block in the following format:
     *   {{NSQF:number,size,quality,format}}
     * which is appended to the prompt text. The resulting string is then JSON-encoded.
     *
     * @return string The JSON-encoded representation of the prompt with NSQF parameters.
     */
    protected function encoder() : string
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

    /**
     * Decodes a JSON-encoded prompt string and sets the image generation parameters.
     *
     * Extracts the NSQF block from the prompt using a regular expression,
     * splits it into the number, size, quality, and response format components,
     * and then uses the corresponding setters to update the prompt.
     *
     * @param string $prompt The JSON-encoded prompt string.
     * @return void
     */
    protected function decoder(string $prompt)
    {
        $promptData = json_decode($prompt);

        preg_match('/{{NSQF:(.*?)}}/', $promptData->prompt, $entities);

        // Default values.
        $p = $promptData->prompt;
        $n = 1;
        $s = [1024, 1024];
        $q = PNSQFquality::STANDARD;
        $f = 'url';

        if (count($entities) > 0) {
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