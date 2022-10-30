<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Readers;

use Zaphyr\Translate\Contracts\ReaderInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class ArrayReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $file): array
    {
        return require $file;
    }
}
