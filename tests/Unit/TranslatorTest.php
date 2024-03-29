<?php

namespace Zaphyr\TranslateTests\Unit;

use Countable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zaphyr\Translate\Contracts\MessageSelectorInterface;
use Zaphyr\Translate\Contracts\TranslatorInterface;
use Zaphyr\Translate\Enum\Reader;
use Zaphyr\Translate\MessageSelector;
use Zaphyr\Translate\Translator;

class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    protected Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator(dirname(__DIR__) . '/TestAsset/lang', 'en');
    }

    protected function tearDown(): void
    {
        unset($this->translator);
    }

    /**
     * ------------------------------------------
     * DIRECTORY
     * ------------------------------------------
     */

    public function testHasDirectory(): void
    {
        self::assertTrue($this->translator->hasDirectory(dirname(__DIR__) . '/TestAsset/lang'));
    }

    public function testAddDirectory(): void
    {
        $this->translator->addDirectory($dir = __DIR__);

        self::assertTrue($this->translator->hasDirectory(dirname(__DIR__) . '/TestAsset/lang'));
        self::assertTrue($this->translator->hasDirectory($dir));
    }

    /**
     * ------------------------------------------
     * LOCALE
     * ------------------------------------------
     */

    public function testLocale(): void
    {
        self::assertEquals('en', $this->translator->getLocale());

        $this->translator->setLocale($locale = 'de');

        self::assertEquals($locale, $this->translator->getLocale());
    }

    /**
     * ------------------------------------------
     * FALLBACK LOCALE
     * ------------------------------------------
     */

    public function testFallback(): void
    {
        self::assertEquals('en', $this->translator->getFallbackLocale());

        $this->translator->setFallbackLocale($locale = 'de');

        self::assertEquals($locale, $this->translator->getFallbackLocale());
    }

    /**
     * ------------------------------------------
     * READER
     * ------------------------------------------
     */

    public function testSetReaderWithDefaultReaderInstance(): void
    {
        self::assertSame(Reader::PHP, $this->translator->getReader());
    }
    public function testSetAndGetReader(): void
    {
        self::assertSame(Reader::JSON, $this->translator->setReader(Reader::JSON)->getReader());
    }

    /**
     * @param Reader $reader
     *
     * @dataProvider readerDataProvider
     */
    public function testAdditionalReader(Reader $reader): void
    {
        self::assertEquals('bar', $this->translator->setReader($reader)->get('messages.foo'));
    }

    /**
     * @return array<Reader[]>
     */
    public static function readerDataProvider(): array
    {
        return [
            [Reader::INI],
            [Reader::JSON],
            [Reader::XML],
            [Reader::YAML],
        ];
    }

    /**
     * ------------------------------------------
     * MESSAGE SELECTOR
     * ------------------------------------------
     */

    public function testGetMessageSelectorReturnsDefaultInstanceByDefault(): void
    {
        self::assertInstanceOf(MessageSelectorInterface::class, $this->translator->getMessageSelector());
    }

    public function testSetMessageSelector(): void
    {
        self::assertInstanceOf(
            TranslatorInterface::class,
            $this->translator->setMessageSelector(new MessageSelector())
        );
    }

    /**
     * ------------------------------------------
     * GET
     * ------------------------------------------
     */

    public function testGetWithSimpleValue(): void
    {
        self::assertEquals('Hello World', $this->translator->get('messages.welcome'));
    }

    public function testGetWithReplace(): void
    {
        self::assertEquals(
            'Hello merloxx, Hello World, Hello YOU!',
            $this->translator->get('messages.greet', ['name' => 'merloxx', 'you' => 'you', 'world' => 'World'])
        );
    }

    public function testGetCanReturnArrayValues(): void
    {
        self::assertEquals(
            ['male' => 'male', 'female' => 'female'],
            $this->translator->get('messages.gender', ['male' => 'male'])
        );
    }

    public function testGetReturnsIdWhenTranslationNotAvailable(): void
    {
        self::assertEquals('messages.nope', $this->translator->get('messages.nope'));
    }

    public function testGetThrowsExceptionWhenNoGroupItemIsPassed(): void
    {
        $this->expectException(RuntimeException::class);

        $this->translator->get('messages');
    }

    public function testGetWithLocale(): void
    {
        self::assertEquals('Hello World', $this->translator->get('messages.welcome'));
        self::assertEquals('Hallo Welt', $this->translator->get('messages.welcome', [], 'de'));
        self::assertEquals('Hello World', $this->translator->get('messages.welcome'));
    }

    public function testGetWithFallbackLocale(): void
    {
        $translator = new Translator(dirname(__DIR__) . '/TestAsset/lang', 'de', 'fr');

        self::assertEquals('Hallo Welt', $translator->get('messages.welcome'));
        self::assertEquals(
            ['male' => 'male', 'female' => 'female'],
            $this->translator->get('messages.gender', ['male' => 'male'])
        );
    }

    public function testGetReturnsIdWhenTranslationForLocaleAndFallbackLocaleIsNotAvailable(): void
    {
        $translator = new Translator(dirname(__DIR__) . '/TestAsset/lang', 'cn', 'en');
        $id = 'not.translated';

        self::assertEquals('Hello World', $translator->get('messages.welcome'));
        self::assertEquals($id, $translator->get($id));

        $translator->setLocale('fr');
        self::assertEquals('Hello World', $translator->get('messages.welcome'));
        self::assertEquals($id, $translator->get($id));

        $translator->setFallbackLocale('it');
        $id = 'messages.welcome';
        self::assertEquals($id, $translator->get($id));
    }

    /**
     * ------------------------------------------
     * CHOICE
     * ------------------------------------------
     */

    public function testChoiceWithTwoSegments(): void
    {
        self::assertEquals('There is one apple', $this->translator->choice('messages.apples', 1));
        self::assertEquals('There are many apples', $this->translator->choice('messages.apples', 2));
    }

    public function testChoiceWithDefinedSegments(): void
    {
        self::assertEquals('There are no pies', $this->translator->choice('messages.pies', 0));
        self::assertEquals('There are some pies', $this->translator->choice('messages.pies', 1.2));
        self::assertEquals('There are some pies', $this->translator->choice('messages.pies', 19));
        self::assertEquals('There are many pies', $this->translator->choice('messages.pies', 20));
        self::assertEquals('There are many pies', $this->translator->choice('messages.pies', 2000));
    }

    public function testChoiceWithReplace(): void
    {
        self::assertEquals('1 minute ago', $this->translator->choice('messages.minutes', 1, ['value' => 1]));
        self::assertEquals('2 minutes ago', $this->translator->choice('messages.minutes', 2, ['value' => 2]));
    }

    public function testChoiceWithCount(): void
    {
        self::assertEquals('There are none', $this->translator->choice('messages.count', 0));
        self::assertEquals('There is one', $this->translator->choice('messages.count', 1));
        self::assertEquals('There are 2', $this->translator->choice('messages.count', 2));
        self::assertEquals('There are 2000', $this->translator->choice('messages.count', 2000));
    }

    public function testChoiceWithArray(): void
    {
        self::assertEquals('There are 3', $this->translator->choice('messages.count', [1, 2, 3]));
    }

    public function testChoiceWithCountable(): void
    {
        $values = new class implements Countable
        {
            public function count(): int
            {
                return 5;
            }
        };

        self::assertEquals('There are 5', $this->translator->choice('messages.count', $values));
    }

    /**
     * ------------------------------------------
     * HAS
     * ------------------------------------------
     */

    public function testHasReturnsTrue(): void
    {
        self::assertTrue($this->translator->has('messages.count'));
    }

    public function testHasReturnsFalse(): void
    {
        self::assertFalse($this->translator->has('messages.nope'));
    }
}
