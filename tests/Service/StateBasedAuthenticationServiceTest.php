<?php
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

class StateBasedAuthenticationServiceTest extends TestCase
{
    public function testIsAuthenticateThrowWhenInInvalidAuthenticationState()
    {
        $stateHandler = \Mockery::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeAuthentication')->andReturnFalse();
        $router = \Mockery::mock(RouterInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('critical')->with('Current request does not need a authentication');
        $registrationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->expectException(RuntimeException::class);
        $registrationService->isAuthenticated();
    }

    public function testIsAuthenticateShouldReturnFalseWhenNotAuthenticated()
    {
        $stateHandler = \Mockery::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeAuthentication')->andReturnTrue();
        $stateHandler->shouldReceive('isAuthenticated')->andReturnFalse();
        $router = \Mockery::mock(RouterInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);
        $registrationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->assertFalse($registrationService->isAuthenticated());
    }

    public function testIsAuthenticateShouldReturnTrueWhenAuthenticated()
    {
        $stateHandler = \Mockery::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeAuthentication')->andReturnTrue();
        $stateHandler->shouldReceive('isAuthenticated')->andReturnTrue();
        $router = \Mockery::mock(RouterInterface::class);
        $logger = \Mockery::mock(LoggerInterface::class);
        $registrationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->assertTrue($registrationService->isAuthenticated());
    }
}
