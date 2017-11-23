<?php
/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\GsspBundle\Exception;

use RuntimeException as CoreRuntimeException;

class RuntimeException extends CoreRuntimeException implements Exception
{
    public static function shouldNotRegister()
    {
        return new self('The current application context does not require a registration');
    }

    public static function requestTypeAlreadyGiven($previous, $type)
    {
        return new self(sprintf(
            'Request type is already given as "%s" and cannot be changed to "%s"',
            $previous,
            $type
        ));
    }
}