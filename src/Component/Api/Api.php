<?php

namespace App\Component\Api;

class Api
{
    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return new Request();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
    }
}
