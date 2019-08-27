<?php

namespace App\Component\Api;

class Response
{
    /**
     * @var int
     */
    private $httpCode = 0;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var array
     */
    private $error = ['code' => 0, 'message' => null];

    /**
     * Response constructor.
     * @param int $httpCode
     * @param array $headers
     * @param string $content
     */
    public function __construct($httpCode = 0, array $headers = [], string $content = '')
    {
        $this->httpCode = $httpCode;
        $this->headers = $headers;
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->httpCode;
    }

    /**
     * @param bool $asJson
     * @return mixed|string
     */
    public function getContent($asJson = true)
    {
        if ($asJson) {
            return json_decode(trim($this->content));
        }
        return trim($this->content);
    }

}
