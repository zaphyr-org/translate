<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Readers;

use InvalidArgumentException;
use Zaphyr\Translate\Contracts\ReaderInterface;
use Zaphyr\Utils\Exceptions\FileNotFoundException;
use Zaphyr\Utils\File;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class XmlReader implements ReaderInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws FileNotFoundException
     */
    public function read(string $file): array
    {
        $contents = File::read($file);

        if (!is_string($contents)) {
            throw new InvalidArgumentException('Could not read file "' . $file . '"');
        }

        $contents = json_encode(simplexml_load_string($contents));

        if (!is_string($contents)) {
            throw new InvalidArgumentException('Could not read encode contents "' . $contents . '"');
        }

        return json_decode($contents, true);
    }
}
