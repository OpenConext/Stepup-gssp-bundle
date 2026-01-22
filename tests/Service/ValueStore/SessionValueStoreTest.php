<?php

declare(strict_types = 1);

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

namespace Tests\Surfnet\GsspBundle\Service\ValueStore;

use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\GsspBundle\Exception\NotFound;
use Surfnet\GsspBundle\Service\ValueStore\SessionValueStore;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionValueStoreTest extends TestCase
{

    #[Test]
    public function canSetProperties()
    {
        /** @var \Mockery\MockInterface $session */
        $session = Mockery::spy(SessionInterface::class);
        $reqStack = Mockery::mock(RequestStack::class);
        $reqStack->shouldReceive('getSession')->andReturn($session);
        $valueStore = new SessionValueStore($reqStack);

        $valueStore->set('test', 1);
        $valueStore->set('value2', 'two');

        $session->shouldHaveReceived('set', ['surfnet/gssp/request/test', 1]);
        $session->shouldHaveReceived('set', ['surfnet/gssp/request/value2', 'two']);

        $this->assertNull(Mockery::close());
    }

    #[Test]
    public function canGetProperties()
    {
        /** @var \Mockery\MockInterface $session */
        $session = Mockery::mock(SessionInterface::class);

        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/test'])->andReturn(true);
        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/value2'])->andReturn(true);
        $session->shouldReceive('get')->withArgs(['surfnet/gssp/request/test'])->andReturn(1);
        $session->shouldReceive('get')->withArgs(['surfnet/gssp/request/value2'])->andReturn(2);

        $reqStack = Mockery::mock(RequestStack::class);
        $reqStack->shouldReceive('getSession')->andReturn($session);
        $valueStore = new SessionValueStore($reqStack);

        $this->assertEquals(1, $valueStore->get('test'));
        $this->assertEquals(2, $valueStore->get('value2'));
    }

    #[Test]
    public function shouldThrowExceptionWhenRequestingUnknownProperties()
    {
        $this->expectException(NotFound::class);
        /** @var \Mockery\MockInterface $session */
        $session = Mockery::mock(SessionInterface::class);

        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/test'])->andReturn(false);

        $reqStack = Mockery::mock(RequestStack::class);
        $reqStack->shouldReceive('getSession')->andReturn($session);
        $valueStore = new SessionValueStore($reqStack);

        $valueStore->get('test');
    }

    #[Test]
    public function knowsIfValueIsSet()
    {
        /** @var \Mockery\MockInterface $session */
        $session = Mockery::mock(SessionInterface::class);
        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/value1'])->andReturn(true);
        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/value2'])->andReturn(false);

        $reqStack = Mockery::mock(RequestStack::class);
        $reqStack->shouldReceive('getSession')->andReturn($session);
        $valueStore = new SessionValueStore($reqStack);

        $this->assertTrue($valueStore->has('value1'));
        $this->assertFalse($valueStore->has('value2'));
    }

    #[Test]
    public function knowsIfValueMatches()
    {
        /** @var \Mockery\MockInterface $session */
        $session = Mockery::mock(SessionInterface::class);
        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/value1'])->andReturn(true);
        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/value2'])->andReturn(true);
        $session->shouldReceive('has')->withArgs(['surfnet/gssp/request/value3'])->andReturn(false);

        $session->shouldReceive('get')->withArgs(['surfnet/gssp/request/value1'])->andReturn(1);
        $session->shouldReceive('get')->withArgs(['surfnet/gssp/request/value2'])->andReturn(2);

        $reqStack = Mockery::mock(RequestStack::class);
        $reqStack->shouldReceive('getSession')->andReturn($session);
        $valueStore = new SessionValueStore($reqStack);

        $this->assertTrue($valueStore->is('value1', 1));
        $this->assertFalse($valueStore->is('value2', 'false'));
        $this->assertFalse($valueStore->is('value3', 3));
    }
}
