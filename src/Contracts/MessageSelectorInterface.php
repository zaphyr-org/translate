<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Contracts;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface MessageSelectorInterface
{
    /**
     * @param string $line
     * @param mixed  $number
     * @param string $locale
     *
     * @return mixed
     */
    public function choose(string $line, $number, string $locale);
}
