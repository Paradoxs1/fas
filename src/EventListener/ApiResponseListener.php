<?php

namespace App\EventListener;

use App\Event\ApiResponseEvent;
use App\Service\GelfLogger;

class ApiResponseListener
{
    /**
     * @var GelfLogger
     */
    protected $logger;

    /**
     * ApiResponseListener constructor.
     * @param GelfLogger $gelfLogger
     */
    public function __construct(GelfLogger $gelfLogger)
    {
        $this->logger = $gelfLogger;
    }

    /**
     * @param ApiResponseEvent $event
     */
    public function onResponse(ApiResponseEvent $event)
    {
        $content = $event->getResponse()->getContent();
        $content = $content->error ?? '';

        $this->logger->info(
            sprintf('%s %s', $event->getMethod(), $event->getPath()),
            sprintf('status: %s; content: %s', $event->getResponse()->getStatus(), $content),
            $event->getData()
        );
    }
}
