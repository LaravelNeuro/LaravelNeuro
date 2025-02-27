<?php

namespace LaravelNeuro\Enums;

/**
 * Enum TuringHistory
 *
 * @package LaravelNeuro
 */
enum TuringHistory: string
{
    case PROMPT = 'PROMPT';
    case RESPONSE = 'RESPONSE';
    case PLUGIN = 'PLUGIN';
    case ERROR = 'ERROR';
    case OTHER = 'OTHER';
}