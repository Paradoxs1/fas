<?php

namespace App\Service;

use App\Entity\Facility;
use App\Entity\Address;
use App\Entity\FacilityLayout;
use App\Entity\CostForecastWeekDay;
use App\Entity\Routine;
use App\Entity\RoutineTemplate;
use App\Repository\CountryRepository;
use App\Repository\CurrencyRepository;
use App\Repository\TenantRepository;
use App\Service\Routine\BBCRMA;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Id\AssignedGenerator;

/**
 * Class RASMigrationService
 * @package App\Service
 */
class RASMigrationService
{
    /**
     * @var CountryRepository
     */
    private $countryRepository;

    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    /**
     * @var TenantRepository
     */
    private $tenantRepository;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * RASMigrationService constructor.
     * @param CountryRepository $countryRepository
     * @param CurrencyRepository $currencyRepository
     * @param TenantRepository $tenantRepository
     * @param ObjectManager $manager
     */
    public function __construct(
        CountryRepository $countryRepository,
        CurrencyRepository $currencyRepository,
        TenantRepository $tenantRepository,
        ObjectManager $manager
    ){
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->tenantRepository = $tenantRepository;
        $this->manager = $manager;
    }

    /**
     * @param $restaurant
     * @return bool
     */
    public function migrateFacility($restaurant): bool
    {
        if (!$restaurant) {
            return false;
        }

        $estCosts = json_decode($restaurant['est_costs'], true);

        //Tenant
        $tenant = $this->tenantRepository->findOneByName('Bruderer Business Consulting GmbH');

        //Selecting right country
        if ('CHF' == $restaurant['currency']) {
            $country = $this->countryRepository->findOneByISO('CHE');
        } else if ('EUR' == $restaurant['currency']) {
            $country = $this->countryRepository->findOneByISO('DEU');
        }

        //Currency
        $currency = $this->currencyRepository->findOneByISO($restaurant['currency']);

        //Address
        $address = new Address();

        $address->setCountry($country);
        $address->setStreet($this->validateStringField($restaurant, 'street'));
        $address->setZip($this->validateStringField($restaurant, 'zip'));
        $address->setCity($this->validateStringField($restaurant, 'city'));

        $this->manager->persist($address);

        //Facility
        $facility = new Facility();

        $facility->setId($restaurant['id']);
        $facility->setName($restaurant['name']);
        $facility->setCreatedAt(new \DateTimeImmutable($restaurant['create_tmstmp']));
        $facility->setModifiedAt(new \DateTimeImmutable($restaurant['mod_tmstmp']));
        $facility->setType('gastronomy');
        $facility->setAddress($address);
        $facility->setTenant($tenant);


        $metadata = $this->manager->getClassMetaData(get_class($facility));
        $metadata->setIdGenerator(new AssignedGenerator());

        $params = [
            'api_key' => $restaurant['api_key'],
            'api_url' => $restaurant['api_url'],
            'tenant_identifier' => $restaurant['identifier'],
            'client_identifier' => $restaurant['client_name'],
            'department' => $restaurant['department'],
            'custom' => [
                'transfere_account_no' => $restaurant['account'],
                'voucher_cash_account_no' => $restaurant['voucher_cash'],
                'debitor_account_no' => $restaurant['debitor_account'],
            ]
        ];

        $routine = new Routine();
        $routineTemplate = $this->manager->getRepository(RoutineTemplate::class)->findOneBy(['name' => BBCRMA::NAME]);
        $routine->setRoutineTemplate($routineTemplate);
        $routine->setName($routineTemplate->getName());
        $routine->setParams(json_encode($params));
        $facility->setRoutine($routine);
        $this->manager->persist($routine);
        $this->manager->persist($facility);

        //Facility Layout (sort of configuration)
        $facilityLayout = new FacilityLayout();

        $facilityLayout->setShifts($this->validateIntegerField($restaurant, 'shifts'));
        $facilityLayout->setDaysInPast($this->validateIntegerField($restaurant, 'allowed_days'));
        $facilityLayout->setCurrency($currency);
        $facilityLayout->setTenant($tenant);
        $facilityLayout->setFacility($facility);

        $this->manager->persist($facilityLayout);

        //Cost Forecast Week
        if ($estCosts) {
            foreach ($estCosts as $dayOfWeek => $data) {
                foreach ($data as $category => $itemData) {
                    $costForecastWeek = new CostForecastWeekDay();
                    $costForecastWeek->setDayOfWeek($dayOfWeek);
                    $costForecastWeek->setFacility($facility);

                    //Picking category
                    if (0 == $category) {
                        $costForecastWeek->setCategory('staffcosts');
                    } else if (1 == $category) {
                        $costForecastWeek->setCategory('operatingcosts');
                    } else {
                        $costForecastWeek->setCategory('costofgoods');
                    }

                    //Picking type
                    if (0 == $itemData['type']) {
                        $costForecastWeek->setType('fix');
                    } else {
                        $costForecastWeek->setType('relative');
                    }

                    $costForecastWeek->setValue($this->validateNumericField($itemData, 'value'));

                    $this->manager->persist($costForecastWeek);
                }
            }
        }

        //Saving everything
        $this->manager->flush();
        /** @var Connection $connection */
        $connection = $this->manager->getConnection();
        $connection->exec('ALTER SEQUENCE facility_id_seq RESTART WITH ' . intval($facility->getId() + 1));

        return true;
    }

    /**
     * @param array $restaurants
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function migrateAllFacilities(array $restaurants): bool
    {
        if (!$restaurants) {
            return false;
        }

        foreach ($restaurants as $restaurant) {
            $this->migrateFacility($restaurant);
        }

        return true;
    }

    /**
     * @param array $data
     * @param string $field
     * @return string
     */
    private function validateStringField(array $data, string $field): string
    {
        return (isset($data[$field]) && $data[$field]) ? $data[$field] : '';
    }

    /**
     * @param array $data
     * @param string $field
     * @return int
     */
    private function validateIntegerField(array $data, string $field): int
    {
        return (isset($data[$field]) && $data[$field] !== '') ? $data[$field] : 0;
    }

    /**
     * @param array $data
     * @param string $field
     * @return float
     */
    private function validateNumericField(array $data, string $field): float
    {
        return (isset($data[$field]) && $data[$field] !== '') ? $data[$field] : 0.00;
    }
}
