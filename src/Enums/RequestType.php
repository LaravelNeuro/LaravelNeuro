<?php

namespace LaravelNeuro\Enums;

enum RequestType: string
{
    case JSON = 'json';
    case MULTIPART = 'multipart';
}