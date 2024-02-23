<?php

namespace LaravelNeuro\LaravelNeuro\Enums;

enum TuringState: string
{
    case INITIAL = 'INITIAL';
    case FINAL = 'FINAL';
    case INTERMEDIARY = 'INTERMEDIARY';
    case PROCESSING = 'PROCESSING';
}