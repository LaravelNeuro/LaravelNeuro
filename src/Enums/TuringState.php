<?php

namespace LaravelNeuro\Enums;

/**
 * Enum TuringState
 *
 * @package LaravelNeuro
 */
enum TuringState: string
{
    case INITIAL = 'INITIAL';
    case FINAL = 'FINAL';
    case INTERMEDIARY = 'INTERMEDIARY';
    case PROCESSING = 'PROCESSING';
}