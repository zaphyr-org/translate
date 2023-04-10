<?php

declare(strict_types=1);

namespace Zaphyr\Translate;

use Countable;
use RuntimeException;
use Zaphyr\Translate\Contracts\MessageSelectorInterface;
use Zaphyr\Translate\Contracts\ReaderInterface;
use Zaphyr\Translate\Contracts\TranslatorInterface;
use Zaphyr\Translate\Enum\Reader;
use Zaphyr\Translate\Readers\ArrayReader;
use Zaphyr\Translate\Readers\IniReader;
use Zaphyr\Translate\Readers\JsonReader;
use Zaphyr\Translate\Readers\XmlReader;
use Zaphyr\Translate\Readers\YamlReader;
use Zaphyr\Utils\Arr;
use Zaphyr\Utils\Str;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Translator implements TranslatorInterface
{
    /**
     * @var string[]
     */
    protected array $directories = [];

    /**
     * @var string
     */
    protected string $locale;

    /**
     * @var string
     */
    protected string $fallbackLocale;

    /**
     * @var Reader
     */
    protected Reader $reader;

    /**
     * @var string[]
     */
    protected static array $readerInstances = [
        'php' => ArrayReader::class,
        'ini' => IniReader::class,
        'json' => JsonReader::class,
        'xml' => XmlReader::class,
        'yaml' => YamlReader::class,
    ];

    /**
     * @var array<string, string[]>
     */
    protected array $parsedIds = [];

    /**
     * @var array<string, array<mixed>>
     */
    protected array $loaded = [];

    /**
     * @var MessageSelectorInterface|null
     */
    protected MessageSelectorInterface|null $messageSelector = null;

    /**
     * @param string|string[] $directories
     * @param string          $locale
     * @param string          $fallbackLocale
     * @param Reader          $reader
     */
    public function __construct(
        string|array $directories,
        string $locale,
        string $fallbackLocale = 'en',
        Reader $reader = Reader::PHP
    ) {
        $this->directories = is_string($directories) ? [$directories] : $directories;
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;

        $this->setReader($reader);
    }

    /**
     * {@inheritdoc}
     */
    public function addDirectory(string $directory): static
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
    public function setLocale(string $locale): static
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
    public function setFallbackLocale(string $fallbackLocale): static
    {
        $this->fallbackLocale = $fallbackLocale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReader(): Reader
    {
        return $this->reader;
    }

    /**
     * {@inheritdoc}
     */
    public function setReader(Reader $reader): static
    {
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
    public function get(
        string $id,
        array $replace = [],
        string|null $locale = null,
        bool $withFallbackLocale = true
    ): array|string {
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
    public function has(string $id, string|null $locale = null, bool $withFallbackLocale = true): bool
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
    protected function localeArray(string|null $locale): array
    {
        return array_filter([$locale ?: $this->locale, $this->fallbackLocale]);
    }

    /**
     * @param string|null $locale
     *
     * @return string
     */
    protected function localeForChoice(string|null $locale): string
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
    protected function getLine(string $locale, string $group, string $item, array $replace): mixed
    {
        $this->load($locale, $group);

        $line = Arr::get($this->loaded, "$locale.$group.$item");

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
            if (file_exists($path = "$directory/$locale/$group." . $this->reader->value)) {
                /** @var ReaderInterface $readerInstance */
                $readerInstance = new static::$readerInstances[$this->reader->value]();
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
