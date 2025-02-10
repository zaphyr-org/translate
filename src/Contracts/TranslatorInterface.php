<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Contracts;

use Countable;
use Zaphyr\Translate\Enum\Reader;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface TranslatorInterface
{
    /**
     * @param string $directory
     *
     * @return $this
     */
    public function addDirectory(string $directory): static;

    /**
     * @param string $directory
     *
     * @return bool
     */
    public function hasDirectory(string $directory): bool;

    /**
     * @return string
     */
    public function getLocale(): string;

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale(string $locale): static;

    /**
     * @return string
     */
    public function getFallbackLocale(): string;

    /**
     * @param string $fallbackLocale
     *
     * @return $this
     */
    public function setFallbackLocale(string $fallbackLocale): static;

    /**
     * @return Reader
     */
    public function getReader(): Reader;

    /**
     * @param Reader $reader
     *
     * @return $this
     */
    public function setReader(Reader $reader): self;

    /**
     * @return MessageSelectorInterface
     */
    public function getMessageSelector(): MessageSelectorInterface;

    /**
     * @param MessageSelectorInterface $messageSelector
     *
     * @return $this
     */
    public function setMessageSelector(MessageSelectorInterface $messageSelector): self;

    /**
     * @param string               $id
     * @param array<string, mixed> $replace
     * @param string|null          $locale
     * @param bool                 $withFallbackLocale
     *
     * @return array<string, mixed>|string
     */
    public function get(
        string $id,
        array $replace = [],
        ?string $locale = null,
        bool $withFallbackLocale = true
    ): array|string;

    /**
     * @param string                           $id
     * @param int|float|array<mixed>|Countable $number
     * @param array<string, mixed>             $replace
     * @param string|null                      $locale
     *
     * @return string
     */
    public function choice(
        string $id,
        int|float|array|Countable $number,
        array $replace = [],
        ?string $locale = null
    ): string;

    /**
     * @param string      $id
     * @param string|null $locale
     * @param bool        $withFallbackLocale
     *
     * @return bool
     */
    public function has(string $id, ?string $locale = null, bool $withFallbackLocale = true): bool;
}
