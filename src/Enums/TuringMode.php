<?php

namespace LaravelNeuro\Enums;

enum TuringMode: string
{
    case COMPLETE = 'COMPLETE';
    case STUCK = 'STUCK';
    case CONTINUE = 'CONTINUE';
}