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

use Surfnet\SamlBundle\SAML2\Extensions\MduiChunk;

final class ServiceNameResolver
{
    private const FALLBACK_LANGUAGE = 'en';

    public static function resolve(?MduiChunk $mdui, string $locale): ?string
    {
        if ($mdui === null) {
            return null;
        }
        $names = $mdui->getDisplayNames();
        if ($names === []) {
            return null;
        }
        $normalizedNames = self::normalizeLanguageKeys($names);
        $values = array_values($normalizedNames);
        $lang = strtolower(substr($locale, 0, 2));
        $raw = $normalizedNames[$lang] ?? $normalizedNames[self::FALLBACK_LANGUAGE] ?? $values[0];
        return ServiceNameFormatter::format($raw);
    }

    /**
     * @param array<string, string> $names
     * @return array<string, string>
     */
    private static function normalizeLanguageKeys(array $names): array
    {
        $normalized = [];
        foreach ($names as $lang => $name) {
            $normalized[strtolower(substr($lang, 0, 2))] ??= $name;
        }
        return $normalized;
    }
}
