<?php

declare(strict_types = 1);

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

use DOMDocument;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\SamlBundle\SAML2\Extensions\MduiChunk;

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

    public function test_has_mdui_returns_false_when_absent(): void
    {
        $this->valueStore
            ->shouldReceive('has')
            ->with('gssp_mdui')
            ->andReturnFalse();
        $this->assertFalse($this->handler->hasMdui());
    }

    public function test_has_mdui_returns_true_when_present(): void
    {
        $this->valueStore
            ->shouldReceive('has')
            ->with('gssp_mdui')
            ->andReturnTrue();
        $this->assertTrue($this->handler->hasMdui());
    }

    public function test_get_mdui_returns_null_when_absent(): void
    {
        $this->valueStore
            ->shouldReceive('has')
            ->with('gssp_mdui')
            ->andReturnFalse();
        $this->assertNull($this->handler->getMdui());
    }

    public function test_get_mdui_returns_chunk_with_display_names(): void
    {
        $chunk = $this->buildMduiChunk(['en' => 'My Service', 'nl' => 'Mijn Dienst']);

        $this->valueStore
            ->shouldReceive('has')
            ->with('gssp_mdui')
            ->andReturnTrue();
        $this->valueStore
            ->shouldReceive('get')
            ->with('gssp_mdui')
            ->andReturn($chunk->toXML());

        $result = $this->handler->getMdui();
        $this->assertInstanceOf(MduiChunk::class, $result);
        $this->assertEquals(['en' => 'My Service', 'nl' => 'Mijn Dienst'], $result->getDisplayNames());
    }

    private function buildMduiChunk(array $displayNames): MduiChunk
    {
        $chunk = new MduiChunk();
        $doc = $chunk->getValue()->ownerDocument;
        $ns = 'urn:oasis:names:tc:SAML:metadata:ui';
        foreach ($displayNames as $lang => $name) {
            $el = $doc->createElementNS($ns, 'mdui:DisplayName');
            $el->setAttribute('xml:lang', $lang);
            $el->textContent = $name;
            $chunk->getValue()->appendChild($el);
        }
        return $chunk;
    }
}
