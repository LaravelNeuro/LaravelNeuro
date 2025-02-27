<?php

namespace LaravelNeuro\Enums;

/**
 * Enum RequestType
 *
 * @package LaravelNeuro
 */
enum RequestType: string
{
    case JSON = 'json';
    case MULTIPART = 'multipart';
}