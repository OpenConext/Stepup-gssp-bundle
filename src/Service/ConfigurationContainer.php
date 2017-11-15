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

namespace Surfnet\GsspBundle\Service;

use Assert\Assertion;

final class ConfigurationContainer
{

    /**
     * The gssp middleware route that does the actual registration.
     *
     * @var string
     */
    private $registrationRoute;

    /**
     * @param array[] $configuration
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct($configuration)
    {
//        Assertion::keyExists($configuration, 'registration_route');
//        Assertion::string($configuration['registration_route']);
        $this->registrationRoute = $configuration['registration_route'];
    }

    /**
     * Getter.
     *
     * @return string
     */
    public function getRegistrationRoute()
    {
        return $this->registrationRoute;
    }
}
