<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Readers;

use InvalidArgumentException;
use Zaphyr\Translate\Contracts\ReaderInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class JsonReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $file): array
    {
        $contents = file_get_contents($file);

        if (!is_string($contents)) {
            throw new InvalidArgumentException('Could not read file "' . $file . '"');
        }

        return json_decode($contents, true);
    }
}
