<?php

namespace LaravelNeuro\LaravelNeuro\Enums;

enum TuringMove: string
{
    case OUTPUT = 'OUTPUT';
    case NEXT = 'NEXT';
    case REPEAT = 'REPEAT';
}