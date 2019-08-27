<?php

namespace App\Service\Email;

use Postal\Client;
use Postal\SendMessage;
use Psr\Log\LoggerInterface;

class EmailSender
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $serverEmail;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * EmailSender constructor.
     * @param string $url
     * @param string $key
     * @param string $serverEmail
     */
    public function __construct(string $url, string $key, string $serverEmail, LoggerInterface $logger)
    {
        $this->url = $url;
        $this->key = $key;
        $this->serverEmail = $serverEmail;
        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @param string $to
     * @param string $subject
     * @return bool
     */
    public function send(string $message, string $to, string $subject): bool
    {
        $client = new Client($this->url, $this->key);
        $SendMessage = new SendMessage($client);
        $SendMessage->to($to);
        $SendMessage->from($this->serverEmail);
        $SendMessage->subject($subject);

        $SendMessage->plainBody($message);
        $SendMessage->htmlBody($message);

        try {
            if ($SendMessage->send()) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }
}