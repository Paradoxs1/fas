<?php

namespace App\Service;

use App\Entity\ReportPositionGroup;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class ReportPositionGroupService
 * @package App\Service
 */
class ReportPositionGroupService
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @var ObjectManager
     */
    private $accountingPositionService;

    /**
     * ReportPositionGroupService constructor.
     * @param ObjectManager $manager
     * @param AccountingPositionService $accountingPositionService
     * @param ParameterBagInterface $params
     */
    public function __construct(
        ObjectManager $manager,
        AccountingPositionService $accountingPositionService,
        ParameterBagInterface $params
    )
    {
        $this->manager                   = $manager;
        $this->accountingPositionService = $accountingPositionService;
        $this->params                    = $params;
    }

    /**
     * @param $name
     * @return ReportPositionGroup
     */
    public function addReportPositionGroup(
        $name
    ): ReportPositionGroup {

        $reportPositionGroup = new ReportPositionGroup();

        $reportPositionGroup->setName($name);

        $this->manager->persist($reportPositionGroup);
        $this->manager->flush();

        return $reportPositionGroup;
    }
}
