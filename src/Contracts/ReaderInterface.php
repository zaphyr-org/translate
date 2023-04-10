<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Contracts;

use InvalidArgumentException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface ReaderInterface
{
    /**
     * @param string $file
     *
     * @throws InvalidArgumentException
     * @return array<string, mixed>
     */
    public function read(string $file): array;
}
