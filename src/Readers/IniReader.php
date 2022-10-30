<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Readers;

use InvalidArgumentException;
use Zaphyr\Translate\Contracts\ReaderInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class IniReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $file): array
    {
        $contents = parse_ini_file($file, true);

        if (!is_array($contents)) {
            throw new InvalidArgumentException('Could not read file "' . $file . '"');
        }

        return $contents;
    }
}
