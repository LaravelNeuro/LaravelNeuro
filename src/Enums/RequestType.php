<?php

namespace LaravelNeuro\LaravelNeuro\Enums;

enum RequestType: string
{
    case JSON = 'json';
    case MULTIPART = 'multipart';
}