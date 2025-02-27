<?php

namespace LaravelNeuro\Enums;

/**
 * Enum TuringMode
 *
 * @package LaravelNeuro
 */
enum TuringMode: string
{
    case COMPLETE = 'COMPLETE';
    case STUCK = 'STUCK';
    case CONTINUE = 'CONTINUE';
}