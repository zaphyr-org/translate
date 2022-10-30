<?php

declare(strict_types=1);

namespace Zaphyr\Translate;

use Zaphyr\Translate\Contracts\ParserInterface;
use Zaphyr\Translate\Readers\ArrayReader;
use Zaphyr\Translate\Readers\IniReader;
use Zaphyr\Translate\Readers\JsonReader;
use Zaphyr\Translate\Readers\XmlReader;
use Zaphyr\Translate\Readers\YamlReader;
use Zaphyr\Utils\File;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Parser implements ParserInterface
{
    /**
     * @var array<string, class-string>
     */
    protected static $readers = [
        'php' => ArrayReader::class,
        'ini' => IniReader::class,
        'json' => JsonReader::class,
        'xml' => XmlReader::class,
        'yml' => YamlReader::class,
        'yaml' => YamlReader::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $directories, string $locale, string $group, string $reader): array
    {
        foreach ($directories as $directory) {
            if (File::exists($path = "$directory/$locale/$group.$reader")) {
                return (new static::$readers[$reader]())->read($path);
            }
        }

        return [];
    }
}
