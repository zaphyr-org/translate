<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Enum;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
enum Reader: string
{
    case PHP = 'php';
    case INI = 'ini';
    case JSON = 'json';
    case XML = 'xml';
    case YAML = 'yaml';
}
