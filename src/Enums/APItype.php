<?php

namespace LaravelNeuro\Enums;

enum APItype: string
{
    case CHATCOMPLETION = 'CHATCOMPLETION';
    case IMAGE = 'IMAGE';
    case BASIC = 'BASIC';
    case TTS = 'TTS';
    case STT = 'STT';
    case VIDEO = 'VIDEO';
}