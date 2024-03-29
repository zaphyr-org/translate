<?php

declare(strict_types=1);

namespace Zaphyr\TranslateTests\Unit;

use PHPUnit\Framework\TestCase;
use Zaphyr\Translate\MessageSelector;

class MessageSelectorTest extends TestCase
{
    /**
     * ------------------------------------------
     * CHOOSE
     * ------------------------------------------
     */

    /**
     * @dataProvider chooseDataProvider
     *
     * @param string $expected
     * @param string $id
     * @param mixed  $number
     */
    public function testChoose(string $expected, string $id, mixed $number): void
    {
        $selector = new MessageSelector();

        self::assertEquals($expected, $selector->choose($id, $number, 'en'));
    }

    /**
     * @return array<mixed>
     */
    public static function chooseDataProvider(): array
    {
        return [
            ['first', 'first', 1],
            ['first', 'first', 10],
            ['first', 'first|second', 1],
            ['second', 'first|second', 10],
            ['second', 'first|second', 0],
            ['first', '{0}  first|{1}second', 0],
            ['first', '{1}first|{2}second', 1],
            ['second', '{1}first|{2}second', 2],
            ['first', '{2}first|{1}second', 2],
            ['second', '{9}first|{10}second', 0],
            ['first', '{9}first|{10}second', 1],
            ['', '{0}|{1}second', 0],
            ['', '{0}first|{1}', 1],
            ['first', '{1.3}first|{2.3}second', 1.3],
            ['second', '{1.3}first|{2.3}second', 2.3],
            ['first line', '{1}first line|{2}second', 1],
            ["first \nline", "{1}first \nline|{2}second", 1],
            ['first', '{0}  first|[1,9]second', 0],
            ['second', '{0}first|[1,9]second', 1],
            ['second', '{0}first|[1,9]second', 10],
            ['first', '{0}first|[2,9]second', 1],
            ['second', '[4,*]first|[1,3]second', 1],
            ['first', '[4,*]first|[1,3]second', 100],
            ['second', '[1,5]first|[6,10]second', 7],
            ['first', '[*,4]first|[5,*]second', 1],
            ['second', '[5,*]first|[*,4]second', 1],
            ['second', '[5,*]first|[*,4]second', 0],
            ['first', '{0}first|[1,3]second|[4,*]third', 0],
            ['second', '{0}first|[1,3]second|[4,*]third', 1],
            ['third', '{0}first|[1,3]second|[4,*]third', 9],
            ['first', 'first|second|third', 1],
            ['second', 'first|second|third', 9],
            ['second', 'first|second|third', 0],
            ['first', '{0}  first | { 1 } second', 0],
            ['first', '[4,*]first | [1,3]second', 100],
        ];
    }
}
