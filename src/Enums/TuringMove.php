<?php

namespace LaravelNeuro\Enums;

/**
 * Enum TuringMove
 *
 * @package LaravelNeuro
 */
enum TuringMove: string
{
    case OUTPUT = 'OUTPUT';
    case NEXT = 'NEXT';
    case REPEAT = 'REPEAT';
}