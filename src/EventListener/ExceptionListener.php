<?php

namespace App\EventListener;

use App\Service\GelfLogger;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionListener
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * ExceptionListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param GelfLogger $gelfLogger
     * @param TranslatorInterface $translator
     */
    public function __construct(TokenStorageInterface $tokenStorage, GelfLogger $gelfLogger, TranslatorInterface $translator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $gelfLogger;
        $this->translator = $translator;
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    private function getReason(\Exception $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'statusCode' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : null,
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception->getLine()) {
            $username = $this->tokenStorage->getToken()->getUsername();
            $url = $event->getRequest()->getUriForPath('') . $event->getRequest()->getRequestUri();
            $reason = $this->getReason($exception);

            $translateMessage = $this->translator->trans('gelf_logger.exception', ['%username%' => $username, '%url%' => $url]);
            $this->logger->error($translateMessage, $reason, null);
        }
    }
}