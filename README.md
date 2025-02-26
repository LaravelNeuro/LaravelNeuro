# LaravelNeuro

![Build Status](https://github.com/LaravelNeuro/LaravelNeuro/actions/workflows/ci.yml/badge.svg)
![Coveralls](https://coveralls.io/repos/github/LaravelNeuro/LaravelNeuro/badge.svg?branch=main)
![Packagist Version](https://img.shields.io/packagist/v/laravel-neuro/core.svg)
![Packagist Downloads](https://img.shields.io/packagist/dt/laravel-neuro/core.svg)

_Join the conversation on the [LaravelNeuro Discord Server](https://discord.gg/pNhSHbBk3Z)!_  

LaravelNeuro is a Laravel package that brings two major features to your application:
1. **Pipelines:** Easily integrate AI models (such as OpenAI, ElevenLabs, and Google Gemini) using a builder-pattern approach. Pipelines abstract API communication via drivers and prompt classes.
2. **Corporations:** Set up and run complex state machines that network AI agents and functions to automate tasks and content generation.

> _See LaravelNeuro in action on [the Anomalous Blog](https://anomalous.laravelneuro.org/) a 100% automated bi-lingual blog with images and voice-over, turning real news articles into supernatural stories, which are then evaluated and classified by the fictional SCP-Foundation._

---

## Installation

Install via Composer:

```bash
composer require laravel-neuro/core
```

Publish the configuration file if you want to override defaults:

```bash
php artisan vendor:publish --tag=laravelneuro-config
```

---

## Pipelines

LaravelNeuro comes with a set of prebuilt Pipelines that let you quickly integrate AI models into your Laravel application.

### Overview

Each Pipeline provides a fluent interface for configuring:
- **Model:** The AI model to be used.
- **Prompt:** The input or conversation prompt for the model.
- **Driver:** An underlying driver (by default, GuzzleDriver) that handles HTTP communication.

By leveraging builder-pattern methods, you can chain configurations and call methods on the driver without directly modifying the Pipeline’s internal state.

### Prebuilt Pipelines

#### ElevenLabs Text-to-Speech  
- **Namespace:** `LaravelNeuro\Pipelines\ElevenLabs\AudioTTS`  
- **Prompt Class:** `LaravelNeuro\Prompts\IVFSprompt`  
Use this pipeline to generate speech audio from text using the ElevenLabs API.

#### OpenAI Pipelines
- **ChatCompletion:**  
  - **Namespace:** `LaravelNeuro\Pipelines\OpenAI\ChatCompletion`  
  - **Prompt Class:** `LaravelNeuro\Prompts\SUAprompt`  
  Leverage GPT-3.5-Turbo to generate conversational responses.
  
- **DallE (Image Generation):**  
  - **Namespace:** `LaravelNeuro\Pipelines\OpenAI\DallE`  
  - **Prompt Class:** `LaravelNeuro\Prompts\PNSQFprompt`  
  Generate images using the Dall-E model. Supports base64, URL, and file storage outputs.
  
- **AudioTTS:**  
  - **Namespace:** `LaravelNeuro\Pipelines\OpenAI\AudioTTS`  
  - **Prompt Class:** `LaravelNeuro\Prompts\IVFSprompt`  
  Convert text to speech via the OpenAI API.

#### Google Gemini Multimodal  
- **Namespace:** `LaravelNeuro\Pipelines\Google\Multimodal`  
- Integrates text and file inputs, providing multiple output formats including text, JSON, and array responses.

### Enabling and Configuring Pipelines

1. **Environment Variables:**  
   Add your API keys to your `.env` file:
   ```dotenv
   OPENAI_API_KEY="your_openai_api_key"
   ELEVENLABS_API_KEY="your_elevenlabs_api_key"
   GOOGLE_API_KEY="your_google_api_key"
   ```

2. **Configuration:**  
   Customize default models and API endpoints by editing the published configuration file (`config/laravelneuro.php`).

3. **Usage Example:**  
   Here's how to use the ChatCompletion pipeline:
   ```php
   use LaravelNeuro\Pipelines\OpenAI\ChatCompletion;
   use LaravelNeuro\Prompts\SUAprompt;

   $prompt = new SUAprompt();
   $prompt->pushSystem("You are a seasoned dungeonmaster for Dungeons and Dragons 3.5 Edition.");
   $prompt->pushUser("I want to create a new character.");
   $prompt->pushAgent("How can I help you?");
   $prompt->pushUser("My character is a shadow kenku...");

   $pipeline = new ChatCompletion();
   $pipeline->setModel('gpt-3.5-turbo'); // Change model if needed
   $pipeline->setPrompt($prompt);

   // For streaming responses:
   foreach ($pipeline->streamText() as $chunk) {
       echo $chunk;
   }

   // For non-stream output:
   echo $pipeline->output();
   ```
   
Each Pipeline also supports various output methods such as `text()`, `json()`, `array()`, and their streaming counterparts.

---

## Corporations

Corporations in LaravelNeuro are state machines that orchestrate the execution of complex tasks by networking multiple AI agents. With Corporations, you can create sophisticated workflows that handle everything from data ingestion and processing to automated content generation.

### Overview

A Corporation is established via the **Incorporate** process:
- **Prebuild:** Use the `lneuro:prebuild` Artisan command to scaffold a new Corporation. This command creates a namespace, folder structure, and a setup file (JSON or PHP) that outlines the corporation's units, agents, and transitions.
- **Install:** Run `lneuro:install` to read the setup file and create all necessary database records, migrations, and files for your Corporation.

### Key Concepts

- **Units:**  
  Represent functional groupings within a Corporation. Each Unit can have multiple Agents and dataset templates that define how data is processed.
  
- **Agents:**  
  An Agent is a single AI component within a Unit. It includes configuration details such as the model, API endpoint, prompt, pipeline class, and role. Agents are responsible for performing specific tasks in the state machine.
  
- **Transitions:**  
  Transitions represent the individual steps in the Corporation's state machine. They are generated from stubs during the installation process and guide the flow of execution across different units.

### Example: Setting Up a Voice Assistant Corporation

1. **Prebuild the Corporation:**  
   ```bash
   php artisan lneuro:prebuild VoiceAssistant
   ```
   This command creates the necessary folder structure and a `setup.json` file in your new Corporation folder.

2. **Edit the Setup File:**  
   Customize `setup.json` to define:
   - **Units:** E.g., Transcription, ChatCompletion, Studio.
   - **Agents:** Specify details like model, pipeline, prompt class, and roles.
   - **Transitions:** Outline the sequential steps for your state machine.

3. **Install the Corporation:**  
   ```bash
   php artisan lneuro:install VoiceAssistant
   ```
   This command reads your setup file and creates the corresponding database records and files.

4. **Run the Corporation:**  
   The `lneuro:run [CORPORATION NAMESPACE] [Optional:TASK]` command executes the state machine, processing transitions and generating a final output based on your defined workflow. You can view the output in the console enabling the --debug flag and log run history by enabling the --history flag.
   ```bash
   php artisan lneuro:run VoiceAssistant "path/to/input.wav" --debug --history
   ```

### Consolidation and Cleanup

To manage database clutter from multiple installations and Corporation runs, LaravelNeuro includes a cleanup command (`lneuro:cleanup`), which removes history entries and can consolidate old Corporation installations with their most current counterparts.

---

## Contributing

Contributions, improvements, and bug fixes are welcome!  
Please review our [CONTRIBUTING.md](CONTRIBUTING.md) file for details on our code of conduct and the process for submitting pull requests.

---

## License

LaravelNeuro is open-sourced software licensed under the [MIT license](LICENSE).

---

This README now provides a structured and detailed overview of both Pipelines and Corporations, showcasing how users can integrate AI models and set up complex state machines in their Laravel applications. Feel free to adjust further based on your project's evolving features and documentation style preferences.
