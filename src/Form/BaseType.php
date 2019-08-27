<?php

namespace App\Form;

use App\Form\EventListener\LoggerListenerInterface;
use Symfony\Component\Form\AbstractType;

class BaseType extends AbstractType
{
    protected $listener;

    public function __construct(LoggerListenerInterface $listener)
    {
        $this->listener = $listener;
    }
}