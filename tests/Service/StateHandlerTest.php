<?php
/**
 * Copyright 2020 SURFnet B.V.
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

class StateHandlerTest extends TestCase
{
    /**
     * @var StateHandler
     */
    private $handler;

    /**
     * @var ValueStore|m\Mock
     */
    private $valueStore;

    protected function setUp(): void
    {
        $this->valueStore = m::mock(ValueStore::class);
        $this->handler = new StateHandler($this->valueStore);
    }

    public function test_can_test_if_scoping_requester_ids_is_present()
    {
        $this->valueStore
            ->shouldReceive('has')
            ->with('scoping_requester_ids')
            ->andReturnFalse();
        $this->assertFalse($this->handler->hasScopingRequesterIds());
    }

    public function test_can_test_if_scoping_requester_ids_is_present_when_present()
    {
        $this->valueStore
            ->shouldReceive('has')
            ->with('scoping_requester_ids')
            ->andReturnTrue();
        $this->assertTrue($this->handler->hasScopingRequesterIds());
    }

    public function test_can_get_scoping_requester_ids()
    {
        $this->valueStore
            ->shouldReceive('get')
            ->with('scoping_requester_ids')
            ->andReturn(['a', 'b', 'c']);
        $this->assertEquals(['a', 'b', 'c'], $this->handler->getScopingRequesterIds());
    }
}
