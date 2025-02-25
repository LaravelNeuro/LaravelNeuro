<?php

namespace LaravelNeuro\Enums;

enum TransitionType: string
{
    case AGENT = 'AGENT';
    case UNIT = 'UNIT';
    case FUNCTION = 'FUNCTION';
}