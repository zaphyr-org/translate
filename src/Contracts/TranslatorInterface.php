<?php

declare(strict_types=1);

namespace Zaphyr\Translate\Contracts;

use Countable;
use InvalidArgumentException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface TranslatorInterface
{
    /**
     * @param string $directory
     *
     * @return TranslatorInterface
     */
    public function addDirectory(string $directory): TranslatorInterface;

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
     * @return TranslatorInterface
     */
    public function setLocale(string $locale): TranslatorInterface;

    /**
     * @return string
     */
    public function getFallbackLocale(): string;

    /**
     * @param string $fallbackLocale
     *
     * @return TranslatorInterface
     */
    public function setFallbackLocale(string $fallbackLocale): TranslatorInterface;

    /**
     * @return string
     */
    public function getReader(): string;

    /**
     * @param string $reader
     *
     * @throws InvalidArgumentException on invalid readers
     *
     * @return TranslatorInterface
     */
    public function setReader(string $reader): TranslatorInterface;

    /**
     * @return MessageSelectorInterface
     */
    public function getMessageSelector(): MessageSelectorInterface;

    /**
     * @param MessageSelectorInterface $messageSelector
     *
     * @return TranslatorInterface
     */
    public function setMessageSelector(MessageSelectorInterface $messageSelector): TranslatorInterface;

    /**
     * @param string               $id
     * @param array<string, mixed> $replace
     * @param string|null          $locale
     * @param bool                 $withFallbackLocale
     *
     * @return array<string, mixed>|string
     */
    public function get(string $id, array $replace = [], ?string $locale = null, bool $withFallbackLocale = true);

    /**
     * @param string                           $id
     * @param int|float|array<mixed>|Countable $number
     * @param array<string, mixed>             $replace
     * @param string|null                      $locale
     *
     * @return string
     */
    public function choice(string $id, $number, array $replace = [], ?string $locale = null): string;

    /**
     * @param string      $id
     * @param string|null $locale
     * @param bool        $withFallbackLocale
     *
     * @return bool
     */
    public function has(string $id, ?string $locale = null, bool $withFallbackLocale = true): bool;
}
