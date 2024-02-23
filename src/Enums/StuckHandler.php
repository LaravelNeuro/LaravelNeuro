<?php

namespace LaravelNeuro\LaravelNeuro\Enums;

enum StuckHandler: string
{
    case REPEAT = 'REPEAT';
    case CONTINUE = 'CONTINUE';
    case TERMINATE = 'TERMINATE';
}