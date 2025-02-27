<?php

namespace LaravelNeuro\Enums;

/**
 * Enum StuckHandler
 *
 * @package LaravelNeuro
 */
enum StuckHandler: string
{
    case REPEAT = 'REPEAT';
    case CONTINUE = 'CONTINUE';
    case TERMINATE = 'TERMINATE';
}