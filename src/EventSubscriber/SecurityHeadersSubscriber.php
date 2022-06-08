<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    private $params;

    public function __construct(ContainerBagInterface $params)
    {
        $this->params = $params;
    }

    public function onResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $securitypolicy = $this->params->get('security');
        $csp = $securitypolicy['csp_policy'];
        $referer = $securitypolicy['referer_policy'];
        $response->headers->set("Content-Security-Policy", $csp);
        $response->headers->set("Referrer-Policy", $referer);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse'
        ];
    }
}