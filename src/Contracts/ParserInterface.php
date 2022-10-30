<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Contracts;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface ParserInterface
{
    /**
     * @param array<string> $directories
     * @param string        $locale
     * @param string        $group
     * @param string        $reader
     *
     * @return array<string, mixed>
     */
    public function load(array $directories, string $locale, string $group, string $reader): array;
}
