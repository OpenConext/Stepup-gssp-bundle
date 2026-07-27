<?php

declare(strict_types = 1);

/**
 * Copyright 2026 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\GsspBundle\Service\ServiceName;

use PHPUnit\Framework\TestCase;

class ServiceNameFormatterTest extends TestCase
{
    public function testShortNameIsReturnedUnchanged(): void
    {
        $this->assertSame('My Service', ServiceNameFormatter::format('My Service'));
    }

    public function testNameOverFortyCharactersIsTruncatedWithEllipsis(): void
    {
        $raw = str_repeat('a', 45);
        $formatted = ServiceNameFormatter::format($raw);

        $this->assertSame(str_repeat('a', 39) . "\u{2026}", $formatted);
        $this->assertSame(40, mb_strlen($formatted));
    }

    public function testNameOfFortyOneCharactersIsTruncatedToThirtyNinePlusEllipsis(): void
    {
        $raw = str_repeat('b', 41);
        $formatted = ServiceNameFormatter::format($raw);

        $this->assertSame(str_repeat('b', 39) . "\u{2026}", $formatted);
        $this->assertSame(40, mb_strlen($formatted));
    }

    public function testNameOfExactlyFortyCharactersIsNotTruncated(): void
    {
        $raw = str_repeat('a', 40);
        $this->assertSame($raw, ServiceNameFormatter::format($raw));
    }

    public function testLeadingAndTrailingWhitespaceIsStripped(): void
    {
        $this->assertSame('My Service', ServiceNameFormatter::format('   My Service   '));
    }

    public function testConsecutiveWhitespaceIsCollapsedToASingleSpace(): void
    {
        $this->assertSame('My Service', ServiceNameFormatter::format('My   Service'));
        $this->assertSame('My Service', ServiceNameFormatter::format("My \t\n Service"));
    }

    public function testControlAndFormatCharactersAreRemoved(): void
    {
        $raw = "My\u{200B}Service\u{0007}";
        $this->assertSame('MyService', ServiceNameFormatter::format($raw));
    }

    public function testTabBetweenWordsIsReplacedWithASingleSpaceNotConcatenated(): void
    {
        $this->assertSame('Name With', ServiceNameFormatter::format("Name\tWith"));
    }

    public function testNewlineBetweenWordsIsReplacedWithASingleSpaceNotConcatenated(): void
    {
        $this->assertSame('Name With', ServiceNameFormatter::format("Name\nWith"));
    }
}
