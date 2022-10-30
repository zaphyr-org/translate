<?php

declare(strict_types=1);

namespace Zaphyr\TranslateTests;

use PHPUnit\Framework\TestCase;
use Zaphyr\Translate\Parser;

class ParserTest extends TestCase
{
    /**
     * ------------------------------------------
     * LOAD
     * ------------------------------------------
     */

    /**
     * @dataProvider validReaderDataProvider
     *
     * @param string $reader
     */
    public function testLoad(string $reader): void
    {
        $parser = new Parser();
        $result = $parser->load([__DIR__ . '/TestAsset/lang'], 'en', 'messages', $reader);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }

    /**
     * @return array<string, array>
     */
    public function validReaderDataProvider(): array
    {
        return [
            'php' => ['php'],
            'ini' => ['ini'],
            'json' => ['json'],
            'xml' => ['xml'],
            'yml' => ['yml'],
            'yaml' => ['yaml'],
        ];
    }

    public function testLoadReturnsEmptyArrayWhenFileCountNotBeLoaded(): void
    {
        $parser = new Parser();
        $result = $parser->load(['nope'], 'de', 'messages', 'ini');

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}

