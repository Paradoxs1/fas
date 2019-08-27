<?php

namespace App\Service\Facility;

use App\Entity\AccountingCategory;
use App\Entity\AccountingPosition;
use App\Entity\FacilityLayout;
use App\Service\FlexParamService;
use App\Service\Routine\RoutineRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ConfigurationParamsHandlerComposite extends ConfigurationParamsHandler
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FlexParamService
     */
    protected $flexParamService;

    /**
     * @var RoutineRegistry
     */
    protected $routineRegistry;

    /**
     * ConfigurationParamsHandlerComposite constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param FlexParamService $flexParamService
     * @param RoutineRegistry $routineRegistry
     */
    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, FlexParamService $flexParamService, RoutineRegistry $routineRegistry)
    {
        parent::__construct($em, $routineRegistry);
        $this->tokenStorage = $tokenStorage;
        $this->flexParamService = $flexParamService;
        $this->handlers = [];
    }

    /**
     * @param ConfigurationParamsHandler $handler
     */
    public function addHandler(ConfigurationParamsHandler $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @return bool
     */
    public function checkChanges(array $requestData, FacilityLayout $facilityLayout): bool
    {
        if (!array_key_exists('enableShiftsCheckbox', $requestData['facility_layout'])) {
            $facilityLayout->setShifts(0);
        }

        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($facilityLayout);

        if (count($changeset)) {
            return true;
        }

        $handlers = $this->getHandlers();
        if ($handlers) {
            /** @var ConfigurationParamsHandler $handler */
            foreach ($handlers as $handler) {
                if ($handler->checkChanges($requestData, $facilityLayout)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $requestData
     * @param FacilityLayout $facilityLayout
     * @param bool $update
     */
    public function addPositions(array $requestData, FacilityLayout $facilityLayout, $update = false)
    {
        $rows = $this->em->getRepository(AccountingPosition::class)->findBy(['facilityLayout' => $facilityLayout]);
        if (!$update) {
            foreach ($rows as $row) {
                $this->em->remove($row);
            }
        }

        $handlers = $this->getHandlers();
        if ($handlers) {
            /** @var ConfigurationParamsHandler $handler */
            foreach ($handlers as $handler) {
                $handler->addPositions($requestData, $facilityLayout, $update);
            }
        }

        $this->addExtraPositions($facilityLayout);
        $this->em->persist($facilityLayout);
    }

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     * @return array
     */
    public function getPositions(array &$data, FacilityLayout $facilityLayout)
    {
        $handlers = $this->getHandlers();
        if ($handlers) {
            /** @var ConfigurationParamsHandler $handler */
            foreach ($handlers as $handler) {
                $handler->getPositions($data, $facilityLayout);
            }
        }

        $data = $this->sortPositions($data, $facilityLayout);

        return $data;
    }

    /**
     * @param array $data
     * @param FacilityLayout $facilityLayout
     * @return array
     */
    private function sortPositions(array $data, FacilityLayout $facilityLayout): array
    {
        $paymentMethodOrder = $facilityLayout->getPaymentMethodOrder();
        if ($paymentMethodOrder) {
            $order = [];
            $categoryRepository = $this->em->getRepository(AccountingCategory::class);
            $paymentMethodOrder = json_decode($paymentMethodOrder);
            foreach ($paymentMethodOrder as $id) {
                $category = $categoryRepository->find($id);
                $order[] = $category->getKey();
            }

            $data = array_merge(array_flip($order), $data);
        }

        return $data;
    }

    /**
     * @param FacilityLayout $facilityLayout
     */
    public function addExtraPositions(FacilityLayout $facilityLayout)
    {
        /** @var AccountingCategory $commentCategory */
        $commentCategory = $this->em->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => FlexParamService::ACCOUNTING_CATEGORY_COMMENT_KEY
            ]
        );

        $accountingPosition = $this->addAccountingPosition($commentCategory, $facilityLayout, 0);
        $this->addParam('value', 'textfield', '', 'frontoffice', 1, $accountingPosition);

        /** @var AccountingCategory $totalSalesCategory */
        $totalSalesCategory = $this->em->getRepository(AccountingCategory::class)->findOneBy(
            [
                'key' => FlexParamService::ACCOUNTING_CATEGORY_TOTAL_SALES_KEY
            ]
        );

        $accountingPosition = $this->addAccountingPosition($totalSalesCategory, $facilityLayout, 0);
        $this->addParam('value', 'currency', '', 'frontoffice', 1, $accountingPosition);
        $this->addParam('catalogNumber', 'textfield', '', 'frontoffice', 1, $accountingPosition);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return '';
    }
}
