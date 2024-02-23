<?php

namespace LaravelNeuro\LaravelNeuro\Enums;

enum TuringHistory: string
{
    case PROMPT = 'PROMPT';
    case RESPONSE = 'RESPONSE';
    case PLUGIN = 'PLUGIN';
    case ERROR = 'ERROR';
    case OTHER = 'OTHER';
}