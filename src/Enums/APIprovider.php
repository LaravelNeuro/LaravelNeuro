<?php

namespace LaravelNeuro\LaravelNeuro\Enums;

enum APIprovider: string
{
    case OPENAI = 'OPENAI';
    case OLLAMA = 'OLLAMA';
    case ELEVENLABS = 'ELEVENLABS';
    case GENERIC = 'GENERIC';
}