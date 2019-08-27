<?php

namespace App\Service;

use Gelf\Message;
use Gelf\Publisher;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GelfLogger
 * @package App\Service
 */
class GelfLogger
{
    // Levels
    const DEBUG = 7;
    const INFO = 5;
    const WARN = 4;
    const ERROR = 3;
    const CRITICAL = 2;

    private static $levels = [
        GelfLogger::DEBUG    => 'DEBUG',
        GelfLogger::INFO     => 'INFO',
        GelfLogger::WARN     => 'WARN',
        GelfLogger::ERROR    => 'ERROR',
        GelfLogger::CRITICAL => 'CRITICAL',
    ];

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var string
     */
    private $loggerName;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $env;

    /**
     * GelfLogger constructor.
     * @param Publisher $publisher
     * @param ContainerInterface $container
     * @param string $name
     * @param string $env
     */
    public function __construct(Publisher $publisher, ContainerInterface $container, string $name, string $env)
    {
        $this->publisher = $publisher;
        $this->container = $container;
        $this->loggerName = $name;
        $this->message = new Message();
        $this->request = $container->get('request_stack')->getMasterRequest();
        $this->env = $env;
    }

    public function debug($message, $context = null, $debug = null)
    {
        $this->sendMessage($message, GelfLogger::DEBUG, $context, $debug);
    }

    public function info($message, $context = null, $debug = null)
    {
        $this->sendMessage($message, GelfLogger::INFO, $context, $debug);
    }

    public function warn($message, $context = null, $debug = null)
    {
        $this->sendMessage($message, GelfLogger::WARN, $context, $debug);
    }

    public function error($message, $context = null, $debug = null)
    {
        $this->sendMessage($message,GelfLogger::ERROR, $context, $debug);
    }

    public function critical($message, $context = null, $debug = null)
    {
        $this->sendMessage($message,GelfLogger::CRITICAL, $context, $debug);
    }

    private function sendMessage($message, $level, $context = null, $debug = null)
    {
        $this->message
            ->setLevel($level)
            ->setFacility($this->loggerName)
            ->setShortMessage(GelfLogger::$levels[$level] . ": " . $message)
            ->setAdditional('tag', sprintf('FAS-%s', $this->env));

            if (isset($this->request)) {
                $this->message->setAdditional('_user_ip', $this->request->getClientIp());
            }

            if (!is_null($context)) {
                $this->message->setAdditional('_context', json_encode($context, JSON_UNESCAPED_UNICODE));
            }

            if (!is_null($debug)) {
                $this->message->setAdditional('_debug', json_encode($debug, JSON_UNESCAPED_UNICODE));
            }
        ;

        $this->publisher->publish($this->message);
    }
}
