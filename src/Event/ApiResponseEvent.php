<?php

namespace App\Event;

use App\Component\Api\Response;
use Symfony\Component\EventDispatcher\Event;

class ApiResponseEvent extends Event
{
    public const NAME = 'api.response';

    /**
     * @var string
     */
    protected $path;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var
     */
    protected $method;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * ApiResponseEvent constructor.
     * @param string $path
     * @param string $method
     * @param array $data
     * @param Response $response
     */
    public function __construct(string $path, string $method, array $data = [], Response $response)
    {
        $this->path = $path;
        $this->method = $method;
        $this->data = $data;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
