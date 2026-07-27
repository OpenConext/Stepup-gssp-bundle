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

final class ServiceNameFormatter
{
    private const MAX_LENGTH = 40;
    private const ELLIPSIS = "\u{2026}";

    public static function format(string $raw): string
    {
        // Collapse whitespace before stripping control chars, so tabs/newlines
        // become a space instead of being deleted (which would concatenate words).
        $collapsed = preg_replace('/\s+/u', ' ', $raw) ?? '';
        $stripped = preg_replace('/[\p{Cc}\p{Cf}]/u', '', $collapsed) ?? '';
        $trimmed = trim($stripped);
        // Truncate to MAX_LENGTH - 1 so the appended ellipsis keeps the total at
        // MAX_LENGTH, per the RFC (OpenConext/Stepup-Gateway#587): "truncated to
        // MAX_CHARACTERS-1... append an ellipsis".
        return mb_strlen($trimmed) > self::MAX_LENGTH
            ? mb_substr($trimmed, 0, self::MAX_LENGTH - 1) . self::ELLIPSIS
            : $trimmed;
    }
}
