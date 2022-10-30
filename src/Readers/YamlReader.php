<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Readers;

use Symfony\Component\Yaml\Yaml;
use Zaphyr\Translate\Contracts\ReaderInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class YamlReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function read(string $file): array
    {
        return Yaml::parseFile($file);
    }
}
