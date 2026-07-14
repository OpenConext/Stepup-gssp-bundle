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
use Surfnet\SamlBundle\SAML2\Extensions\MduiChunk;

class ServiceNameResolverTest extends TestCase
{
    public function testItReturnsNullWhenMduiChunkIsNull(): void
    {
        $this->assertNull(ServiceNameResolver::resolve(null, 'nl_NL'));
    }

    public function testItReturnsNullWhenMduiChunkHasNoDisplayNames(): void
    {
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui"></mdui:UIInfo>'
        );

        $this->assertNull(ServiceNameResolver::resolve($mdui, 'nl_NL'));
    }

    public function testItPrefersExactLocaleMatchOverEnglish(): void
    {
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">'
            . '<mdui:DisplayName xml:lang="en">English Service Name</mdui:DisplayName>'
            . '<mdui:DisplayName xml:lang="nl">Nederlandse Dienstnaam</mdui:DisplayName>'
            . '</mdui:UIInfo>'
        );

        $this->assertSame('Nederlandse Dienstnaam', ServiceNameResolver::resolve($mdui, 'nl_NL'));
    }

    public function testItFallsBackToEnglishWhenRequestedLocaleIsAbsent(): void
    {
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">'
            . '<mdui:DisplayName xml:lang="en">English Service Name</mdui:DisplayName>'
            . '<mdui:DisplayName xml:lang="de">Deutscher Dienstname</mdui:DisplayName>'
            . '</mdui:UIInfo>'
        );

        $this->assertSame('English Service Name', ServiceNameResolver::resolve($mdui, 'nl_NL'));
    }

    public function testItFallsBackToFirstAvailableWhenNeitherRequestedLocaleNorEnglishIsPresent(): void
    {
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">'
            . '<mdui:DisplayName xml:lang="de">Deutscher Dienstname</mdui:DisplayName>'
            . '<mdui:DisplayName xml:lang="fr">Nom du service francais</mdui:DisplayName>'
            . '</mdui:UIInfo>'
        );

        $this->assertSame('Deutscher Dienstname', ServiceNameResolver::resolve($mdui, 'nl_NL'));
    }

    public function testItMatchesLocaleWithRegionSubtagAgainstLanguageOnlyDisplayName(): void
    {
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">'
            . '<mdui:DisplayName xml:lang="en-US">English Service Name</mdui:DisplayName>'
            . '<mdui:DisplayName xml:lang="nl">Nederlandse Dienstnaam</mdui:DisplayName>'
            . '</mdui:UIInfo>'
        );

        $this->assertSame('English Service Name', ServiceNameResolver::resolve($mdui, 'en_GB'));
    }

    public function testItMatchesUppercaseXmlLangAgainstLowercaseLocale(): void
    {
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">'
            . '<mdui:DisplayName xml:lang="EN">English Service Name</mdui:DisplayName>'
            . '</mdui:UIInfo>'
        );

        $this->assertSame('English Service Name', ServiceNameResolver::resolve($mdui, 'en_US'));
    }

    public function testItFormatsTheResolvedRawNameThroughServiceNameFormatter(): void
    {
        $longName = str_repeat('a', 45);
        $mdui = MduiChunk::fromXML(
            '<mdui:UIInfo xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">'
            . '<mdui:DisplayName xml:lang="en">' . $longName . '</mdui:DisplayName>'
            . '</mdui:UIInfo>'
        );

        $this->assertSame(str_repeat('a', 40) . "\u{2026}", ServiceNameResolver::resolve($mdui, 'en_US'));
    }
}
