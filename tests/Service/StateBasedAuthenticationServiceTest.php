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

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\GsspBundle\Exception\RuntimeException;
use Symfony\Component\Routing\RouterInterface;

class StateBasedAuthenticationServiceTest extends TestCase
{
    public function testIsAuthenticateThrowWhenInInvalidAuthenticationState()
    {
        $stateHandler = m::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeAuthentication')->andReturnFalse();
        $router = m::mock(RouterInterface::class);
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('critical')->with('Current request does not need an authentication');
        $authenticationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->expectException(RuntimeException::class);
        $authenticationService->isAuthenticated();
    }

    public function testIsAuthenticateShouldReturnFalseWhenNotAuthenticated()
    {
        $stateHandler = m::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeAuthentication')->andReturnTrue();
        $stateHandler->shouldReceive('isAuthenticated')->andReturnFalse();
        $router = m::mock(RouterInterface::class);
        $logger = m::mock(LoggerInterface::class);
        $authenticationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->assertFalse($authenticationService->isAuthenticated());
    }

    public function testIsAuthenticateShouldReturnTrueWhenAuthenticated()
    {
        $stateHandler = m::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('isRequestTypeAuthentication')->andReturnTrue();
        $stateHandler->shouldReceive('isAuthenticated')->andReturnTrue();
        $router = m::mock(RouterInterface::class);
        $logger = m::mock(LoggerInterface::class);
        $registrationService = new StateBasedAuthenticationService(
            $stateHandler,
            $router,
            $logger
        );
        $this->assertTrue($registrationService->isAuthenticated());
    }

    public function test_can_request_scoping_requester_ids_when_not_present()
    {
        $stateHandler = m::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('hasScopingRequesterIds')->andReturnFalse();
        $authenticationService = new StateBasedAuthenticationService(
            $stateHandler,
            m::mock(RouterInterface::class),
            m::mock(LoggerInterface::class)
        );
        $this->assertEmpty($authenticationService->getScopingRequesterIds());
    }
    public function test_can_request_scoping_requester_ids_when_present()
    {
        $stateHandler = m::mock(StateHandlerInterface::class);
        $stateHandler->shouldReceive('hasScopingRequesterIds')->andReturnTrue();
        $stateHandler->shouldReceive('getScopingRequesterIds')->andReturn(['a', 'b', 'c']);
        $authenticationService = new StateBasedAuthenticationService(
            $stateHandler,
            m::mock(RouterInterface::class),
            m::mock(LoggerInterface::class)
        );
        $this->assertEquals(['a', 'b', 'c'], $authenticationService->getScopingRequesterIds());
    }
}
