<?php

declare(strict_types = 1);

/**
 * Copyright 2019 SURFnet B.V.
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

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\RuntimeException;
use Symfony\Component\Routing\RouterInterface;

class StateBasedRegistrationServiceTest extends TestCase
{

    public function testIsRegisteredShouldThrowInInvalidRegistrationState()
    {
        $stateHandler = \Mockery::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeRegistration')->andReturnFalse();
        $router = \Mockery::mock(RouterInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('critical')->with('Current request does not need a registration');
        $registrationService = new StateBasedRegistrationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->expectException(RuntimeException::class);
        $registrationService->isRegistered();
    }

    public function testIsRegisteredShouldReturnFalseWhenNotYetRegistered()
    {
        $stateHandler = \Mockery::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeRegistration')->andReturnTrue();
        $stateHandler->shouldReceive('hasSubjectNameId')->andReturnFalse();
        $router = \Mockery::mock(RouterInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);
        $registrationService = new StateBasedRegistrationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->assertFalse($registrationService->isRegistered());
    }

    public function testIsRegisteredShouldReturnFalseWhenRegistered()
    {
        $stateHandler = \Mockery::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeRegistration')->andReturnTrue();
        $stateHandler->shouldReceive('hasSubjectNameId')->andReturnTrue();
        $router = \Mockery::mock(RouterInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);
        $registrationService = new StateBasedRegistrationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->assertTrue($registrationService->isRegistered());
    }
}
