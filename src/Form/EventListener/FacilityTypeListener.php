<?php

namespace App\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FacilityTypeListener extends AbstractLogger implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'onPostSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSetData(FormEvent $event)
    {
        $this->fieldForRequest = $event->getData()->getName();
    }

    public function onPostSubmit(FormEvent $event)
    {
        $this->generalPostSubmit($event, self::FACILITY_OR_TENANT_FIELD, self::FACILITY);
    }
}