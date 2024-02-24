# Welcome to LaravelNeuro

![Build Status](https://github.com/LaravelNeuro/LaravelNeuro/actions/workflows/ci.yml/badge.svg)
![Coveralls](https://coveralls.io/repos/github/LaravelNeuro/LaravelNeuro/badge.svg?branch=main)
![Packagist Version](https://img.shields.io/packagist/v/laravel-neuro/core.svg)
![Packagist Downloads](https://img.shields.io/packagist/dt/laravel-neuro/core.svg)

_Join the discussion on the [LaravelNeuro Discord Server](https://discord.gg/pNhSHbBk3Z)!_

This Laravel package enhances your PHP Laravel application by introducing two powerful features:
1. **Integrating AI APIs** into your application code using Pipeline and Prompt Classes.
2. **Setting up and running complex state machines** that network any number of generative AI agents and functions to automate tasks and content generation.

## Features

### Prebuilt Pipelines

LaravelNeuro ships with a pre-configured set of Pipelines for fast implementation, including:

- **ElevenLabs Text to Speech**
    - **Namespace**: `LaravelNeuro\LaravelNeuro\Pipelines\ElevenLabs\AudioTTS`
    - **Prompt Class**: `LaravelNeuro\LaravelNeuro\Prompts\IVFSprompt`
- **OpenAI**
    - **ChatCompletion**
        - **Namespace**: `LaravelNeuro\LaravelNeuro\Pipelines\OpenAI\ChatCompletion`
        - **Prompt Class**: `LaravelNeuro\LaravelNeuro\Prompts\SUAprompt`
    - **DallE**
        - **Namespace**: `LaravelNeuro\LaravelNeuro\Pipelines\OpenAI\DallE`
        - **Prompt Class**: `LaravelNeuro\LaravelNeuro\Prompts\PNSQFprompt`
    - **AudioTTS**
        - **Namespace**: `LaravelNeuro\LaravelNeuro\Pipelines\OpenAI\AudioTTS`
        - **Prompt Class**: `LaravelNeuro\LaravelNeuro\Prompts\IVFSprompt`

All pipelines extend the basic `LaravelNeuro\LaravelNeuro\Pipeline`, which itself extends the `ApiAdapter` Class, facilitating the transmission of prompts and reception of responses via Guzzle.

#### Enabling Pipelines

Enable the OpenAI and ElevenLabs pipelines by adding your API key to your Laravel application's `.env` file:

```plaintext
OPENAI_API_KEY="your_api_key"
ELEVENLABS_API_KEY="your_api_key"
```

Change the default models for each Pipeline by publishing the `lneuro` configuration file if desired:

```bash
php artisan vendor:publish --tag=laravelneuro-config
```

This is not strictly necessary. Even when using one of the prebuilt Pipelines, you can switch from the default model to any compatible model simply by calling the `setModel` method on your `Pipeline` object, passing the name of the model as a string parameter. Example:
```php
use LaravelNeuro\LaravelNeuro\Pipelines\OpenAI\ChatCompletion;

$pipeline = new ChatCompletion();
$pipeline->setModel('gpt-4-turbo-preview'); //this changes the model from the default gpt-3.5-turbo-0125
```

#### Example Usage

Here's an example of using OpenAI's ChatCompletion in a Laravel script with a streaming response:

```php
use LaravelNeuro\LaravelNeuro\Pipelines\OpenAI\ChatCompletion;
use LaravelNeuro\LaravelNeuro\Prompts\SUAprompt;

$prompt = new SUAprompt();
$pipeline = new ChatCompletion();

$prompt->pushSystem("You are a seasoned dungeonmaster and play Dungeons and Dragons 3.5th Edition with me.");
$prompt->pushUser("I want to create a new D&D character.");
$prompt->pushAgent("How can I help you with your character creation?");
$prompt->pushUser("My character is a shadow kenku...");

echo "response:\n";
$pipeline->setPrompt($prompt);

$stream = $pipeline->streamText();
foreach ($stream as $output) {
    print_r($output);
}

//sample response:
/* 
That sounds like an interesting character concept! The shadow kenku is a homebrew race that combines the traits of kenku and shadow creatures. Let's work on creating your shadow kenku character together. 

First, let's determine your ability scores. As a shadow kenku, you might want to focus on Dexterity and Charisma for your abilities. What ability scores do you want to prioritize for your character? 
*/
```

#### Output Methods

The OpenAi ChatCompletion Pipeline also allows for various output methods including `text`, `json`, `array`, `jsonStream`, and `arrayStream`, whereas the basic Pipeline class ships with the more generic `output` and `stream` methods.

### State Machines

LaravelNeuro State Machines, called Corporations, are simple to setup and can network AI APIs and scripts for complex tasks.

#### Setup

For LaravelNeuro state machines to work, migrate its Eloquent models with:

```bash
php artisan lneuro:migrate
```

#### Creating a Voice Assistant

Example setup for a voice-to-voice chat assistant:

1. **Create the Corporation folder and example setup file:**

```bash
php artisan lneuro:prebuild VoiceAssistant
```

2. **Fill out the `setup.json` file** with the necessary AI models (speech to text, text generation, text to speech).

//setup.json example
```json
{
    "name": "Voice Assistant",
    "nameSpace": "VoiceAssistant",
    "description": "Ingest natural speech audio, query a chat-completion agent, then apply a TTS model to the output.",
    "charta": "",
    "units": [
      {
        "name": "Transcription",
        "description": "This Unit ingests a file path and outputs transcribed text.",
        "agents": [
          {
            "name": "Transcriber",
            "model": "whisper-1",
            "pipeline": "LaravelNeuro\\LaravelNeuro\\Pipelines\\OpenAI\\Whisper",   
            "promptClass": "LaravelNeuro\\LaravelNeuro\\Prompts\\FSprompt",   
            "validateOutput": false
          }
        ],
        "defaultReceiver": {
          "type": "AGENT",
          "name": "Transcriber"
        }
      },
      {
        "name": "ChatCompletion",
        "description": "This unit uses transcribed text as prompts to generate text responses.",
        "agents": [
          {
            "name": "Chatbot",
            "model": "gpt-3.5-turbo-1106",
            "pipeline": "LaravelNeuro\\LaravelNeuro\\Pipelines\\OpenAI\\ChatCompletion",     
            "role": "You are a helpful assistant.",   
            "validateOutput": false
          }
        ],
        "defaultReceiver": {
          "type": "AGENT",
          "name": "Chatbot"
        }
      },
      {
        "name": "Studio",
        "description": "This unit takes generated text responses and outputs voice-over.",
        "agents": [
          {
            "name": "Speaker",
            "model": "tts-1",
            "apiType": "TTS",
            "pipeline": "LaravelNeuro\\LaravelNeuro\\Pipelines\\OpenAI\\AudioTTS",
            "promptClass": "LaravelNeuro\\LaravelNeuro\\Prompts\\IVFSprompt",    
            "prompt": "{{Head:data}}{{VFS:nova}}",
            "validateOutput": false
          }
        ],
        "defaultReceiver": {
          "type": "AGENT",
          "name": "Speaker"
        }
      }
    ],
    "transitions": [
      {
        "type": "UNIT",
        "transitionName": "Transcription",
        "transitionHandle": "Transcription"
      },
      {
        "type": "UNIT",
        "transitionName": "ChatCompletion",
        "transitionHandle": "ChatCompletion"
      },
      {
        "type": "UNIT",
        "transitionName": "Studio",
        "transitionHandle": "Studio"
      }
    ]
}
```

3. **Install and run your Corporation:**

```bash
php artisan lneuro:install VoiceAssistant
```

This setup does not require coding but offers hooks for custom logic injection.

### Advanced Use-Cases

More detailed use-cases and documentation will be available on the separate documentation website soon.