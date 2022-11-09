<?php

declare(strict_types=1);

namespace Zaphyr\Translate;

use Countable;
use InvalidArgumentException;
use RuntimeException;
use Zaphyr\Translate\Contracts\MessageSelectorInterface;
use Zaphyr\Translate\Contracts\ReaderInterface;
use Zaphyr\Translate\Contracts\TranslatorInterface;
use Zaphyr\Translate\Readers\ArrayReader;
use Zaphyr\Translate\Readers\IniReader;
use Zaphyr\Translate\Readers\JsonReader;
use Zaphyr\Translate\Readers\XmlReader;
use Zaphyr\Translate\Readers\YamlReader;
use Zaphyr\Utils\Arr;
use Zaphyr\Utils\File;
use Zaphyr\Utils\Str;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Translator implements TranslatorInterface
{
    /**
     * @var string[]
     */
    protected $directories = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $fallbackLocale;

    /**
     * @var string
     */
    protected $reader;

    /**
     * @const string
     */
    public const READER_PHP = 'php';

    /**
     * @const string
     */
    public const READER_INI = 'ini';

    /**
     * @const string
     */
    public const READER_JSON = 'json';

    /**
     * @const string
     */
    public const READER_XML = 'xml';

    /**
     * @const string
     */
    public const READER_YAML = 'yaml';

    /**
     * @var string[]
     */
    protected static $readerInstances = [
        self::READER_PHP => ArrayReader::class,
        self::READER_INI => IniReader::class,
        self::READER_JSON => JsonReader::class,
        self::READER_XML => XmlReader::class,
        self::READER_YAML => YamlReader::class,
    ];

    /**
     * @var array<string, string[]>
     */
    protected $parsedIds = [];

    /**
     * @var array<string, array<mixed>>
     */
    protected $loaded = [];

    /**
     * @var MessageSelectorInterface
     */
    protected $messageSelector;

    /**
     * @param string|string[] $directories
     * @param string          $locale
     * @param string          $fallbackLocale
     * @param string          $reader
     */
    public function __construct(
        $directories,
        string $locale,
        string $fallbackLocale = 'en',
        string $reader = self::READER_PHP
    ) {
        $this->directories = is_string($directories) ? [$directories] : $directories;
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;

        $this->setReader($reader);
    }

    /**
     * {@inheritdoc}
     */
    public function addDirectory(string $directory): TranslatorInterface
    {
        if (!$this->hasDirectory($directory)) {
            $this->directories[] = $directory;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDirectory(string $directory): bool
    {
        return in_array($directory, $this->directories, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(string $locale): TranslatorInterface
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function setFallbackLocale(string $fallbackLocale): TranslatorInterface
    {
        $this->fallbackLocale = $fallbackLocale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReader(): string
    {
        return $this->reader;
    }

    /**
     * {@inheritdoc}
     */
    public function setReader(string $reader): TranslatorInterface
    {
        $reader = strtolower($reader);
        $validReaders = [self::READER_PHP, self::READER_INI, self::READER_JSON, self::READER_XML, self::READER_YAML];

        if (!in_array($reader, $validReaders)) {
            throw new InvalidArgumentException(
                'The reader "' . $reader . '" is invalid. ' .
                'Valid translator readers are "' . implode('", "', $validReaders) . '"'
            );
        }
        $this->reader = $reader;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageSelector(): MessageSelectorInterface
    {
        if ($this->messageSelector === null) {
            $this->messageSelector = new MessageSelector();
        }

        return $this->messageSelector;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageSelector(MessageSelectorInterface $messageSelector): TranslatorInterface
    {
        $this->messageSelector = $messageSelector;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id, array $replace = [], ?string $locale = null, bool $withFallbackLocale = true)
    {
        [$group, $item] = $this->parseId($id);
        $locales = $withFallbackLocale ? $this->localeArray($locale) : [$locale ?: $this->locale];

        foreach ($locales as $localeItem) {
            if (!is_null($line = $this->getLine($localeItem, $group, $item, $replace))) {
                break;
            }
        }

        return $line ?? $id;
    }

    /**
     * {@inheritdoc}
     */
    public function choice(string $id, $number, array $replace = [], string $locale = null): string
    {
        $locale = $this->localeForChoice($locale);
        $line = $this->get($id, $replace, $locale);

        if (!is_string($line)) {
            return '';
        }

        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;

        $line = $this->getMessageSelector()->choose($line, $number, $locale);

        return $this->makeReplacements($line, $replace);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id, ?string $locale = null, bool $withFallbackLocale = true): bool
    {
        return $this->get($id, [], $locale, $withFallbackLocale) !== $id;
    }

    /**
     * @param string $id
     *
     * @return array<int, string>
     */
    protected function parseId(string $id): array
    {
        if (isset($this->parsedIds[$id])) {
            return $this->parsedIds[$id];
        }

        $segments = explode('.', $id);
        $parsed = $this->parseIdSegments($segments);

        return $this->parsedIds[$id] = $parsed;
    }

    /**
     * @param array<string> $segments
     *
     * @return array<int, string>
     */
    protected function parseIdSegments(array $segments): array
    {
        $group = $segments[0];

        $item = count($segments) === 1 ? null : implode('.', array_slice($segments, 1));

        if ($item === null) {
            throw new RuntimeException('Missing item for group "' . $group . '"');
        }

        return [$group, $item];
    }

    /**
     * @param string|null $locale
     *
     * @return array<string>
     */
    protected function localeArray(?string $locale): array
    {
        return array_filter([$locale ?: $this->locale, $this->fallbackLocale]);
    }

    /**
     * @param string|null $locale
     *
     * @return string
     */
    protected function localeForChoice(?string $locale): string
    {
        return $locale ?: $this->locale ?: $this->fallbackLocale;
    }

    /**
     * @param string               $locale
     * @param string               $group
     * @param string               $item
     * @param array<string, mixed> $replace
     *
     * @return mixed
     */
    protected function getLine(string $locale, string $group, string $item, array $replace)
    {
        $this->load($locale, $group);

        $line = Arr::get($this->loaded[$locale][$group], $item);

        if (is_string($line)) {
            return empty($replace) ? $line : $this->makeReplacements($line, $replace);
        }

        if (is_array($line) && count($line) > 0) {
            foreach ($line as $key => $value) {
                $line[$key] = $this->makeReplacements($value, $replace);
            }

            return $line;
        }

        return null;
    }

    /**
     * @param string $locale
     * @param string $group
     */
    protected function load(string $locale, string $group): void
    {
        if ($this->isLoaded($locale, $group)) {
            return;
        }

        foreach ($this->directories as $directory) {
            if (File::exists($path = "$directory/$locale/$group.$this->reader")) {
                /** @var ReaderInterface $readerInstance */
                $readerInstance = new static::$readerInstances[$this->reader]();
                $this->loaded[$locale][$group] = $readerInstance->read($path);
            }
        }
    }

    /**
     * @param string $locale
     * @param string $group
     *
     * @return bool
     */
    protected function isLoaded(string $locale, string $group): bool
    {
        return isset($this->loaded[$locale][$group]);
    }

    /**
     * @param string                   $line
     * @param array<string, int|float> $replace
     *
     * @return string
     */
    protected function makeReplacements(string $line, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $value = (string)$value;

            $line = str_replace(
                ['%' . $key . '%', '%' . Str::upper($key) . '%', '%' . Str::upperFirst($key) . '%'],
                [$value, Str::upper($value), Str::upperFirst($value)],
                $line
            );
        }

        return $line;
    }
}
