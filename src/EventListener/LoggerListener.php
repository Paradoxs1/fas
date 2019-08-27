<?php

namespace App\EventListener;

use App\Entity\EntityInterface;
use App\Entity\Report;
use App\Form\EventListener\AbstractLogger;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class LoggerListener extends AbstractLogger
{
    private $className;

    private $findField;

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Report) {
            if (isset($this->request)) {
                $data = ['id' => $entity->getId()] + $this->deleteKeyArray(['date-changed'], $this->request->request->all());
                $facility = $entity->getFacilityLayout()->getFacility();

                $translateMessage = $this->translator->trans('gelf_logger.cashier_report_create', [
                    '%username%' => $this->tokenStorage->getToken()->getUsername(),
                    '%id%' => $entity->getId(),
                    '%name%' => $facility->getName(),
                    '%facility_id%' => $facility->getId(),
                    '%date%' => $entity->getStatementDate()->format('Y-m-d')
                ]);

                $this->logger->info($translateMessage, null, $data);
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof EntityInterface) {
            $this->getFindFieldsEntity($entity);

            if (!is_null($entity->getDeletedAt())) {
                $translateMessage = $this->getTranslateMessage(
                    self::DELETE,
                    $this->tokenStorage->getToken()->getUsername(),
                    $this->className,
                    $this->findField,
                    $entity->getId()
                );

                $this->logger->info($translateMessage);
            }
        }
    }

    /**
     * @param EntityInterface $entity
     * @return void
     */
    private function getFindFieldsEntity(EntityInterface $entity): void
    {
        $this->className = strtolower(substr(strrchr(get_class($entity), '\\'), 1));
        $this->findField = $this->className == self::ACCOUNT ? $entity->getLogin() : $entity->getName();
    }
}