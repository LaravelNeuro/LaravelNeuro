<?php

namespace LaravelNeuro\Enums;

/**
 * Enum TransitionType
 *
 * @package LaravelNeuro
 */
enum TransitionType: string
{
    case AGENT = 'AGENT';
    case UNIT = 'UNIT';
    case FUNCTION = 'FUNCTION';
}