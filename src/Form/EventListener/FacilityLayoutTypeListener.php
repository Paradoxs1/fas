<?php

namespace App\Form\EventListener;

use App\Entity\FacilityLayout;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FacilityLayoutTypeListener extends AbstractLogger implements EventSubscriberInterface
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
        $this->fieldForRequest = $event->getData()->getId();
    }

    public function onPostSubmit(FormEvent $event)
    {
        if (isset($this->request)) {
            $route = $this->request->get('_route');
            $translateMessage = '';
            $this->data = $this->deleteKeyArray(['save', '_token'], $this->request->request->all());

            if ($event->getForm()->isValid()) {
                if ($trigger = $route == self::ROUTE_FACILITY_CONFIGURATION) {
                    //$this->data['facility_layout']['accountingInterface']['configuration'] = json_decode($this->data['facility_layout']['accountingInterface']['configuration'], true);
                    $this->data = ['id' => (int) $this->fieldForRequest + 1] + $this->data;
                    $facility = $event->getData()->getFacility();

                    $translateMessage = $this->getTranslateMessage(
                        self::ROUTE_FACILITY_CONFIGURATION,
                        $this->tokenStorage->getToken()->getUsername(),
                        self::FACILITY,
                        $facility->getName(),
                        $facility->getId()
                    );
                } elseif ($trigger = $route == self::ROUTE_TENANT_CONFIGURATION) {
                    $facilityLayout = $this->em->getRepository(FacilityLayout::class)->findOneBy([], ['id' => 'desc']);
                    $this->data = ['id' => $this->fieldForRequest ? $this->fieldForRequest : $facilityLayout->getId() + 1] + $this->data;
                    $tenant = $this->tokenStorage->getToken()->getUser()->getTenant();

                    $translateMessage = $this->getTranslateMessage(
                        self::ROUTE_TENANT_CONFIGURATION,
                        $this->tokenStorage->getToken()->getUsername(),
                        self::TENANT,
                        $tenant->getName(),
                        $tenant->getId()
                    );
                }

                $trigger ? $this->logger->info($translateMessage, null, $this->data) : false ;
            }
        }
    }
}
