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

namespace Surfnet\GsspBundle\EventSubscriber;

use Surfnet\GsspBundle\Saml\StateHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Add stepup request header to all responses.
 */
final class StepupRequestIdResponseListener implements EventSubscriberInterface
{
    private $stateHandler;

    public function __construct(StateHandler $stateHandler)
    {
        $this->stateHandler = $stateHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse']
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->stateHandler->hasStepupRequestId()) {
            return;
        }
        $event->getResponse()->headers->set('X-Stepup-Request-Id', $this->stateHandler->getStepupRequestId());
    }
}
