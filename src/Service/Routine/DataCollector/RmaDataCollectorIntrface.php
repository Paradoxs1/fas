<?php

namespace App\Service\Routine\DataCollector;

use App\Entity\Facility;
use App\Entity\Report;


interface RmaDataCollectorIntrface
{
    public function getCreditCards(Report $report = null, array $reports = [], array &$data, array $requestData = []): void;


    public function getIssuedVouchers(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void;


    public function getAcceptedVouchers(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void;


    public function getSales(Report $report = null, array $reports = [], array &$data, array $requestData = []): void;


    public function getBills(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void;


    public function getExpenses(array $reports = [], array &$data, array $requestData = [], Facility $facility = null): void;


    public function getCash(array $reports = [],array &$data, array $requestData = [], Facility $facility = null): void;


    public function getCigarettes(array $reports = [], array &$data, array $requestData = []): void;


    public function getTips(array $reports = [], array &$data, array $requestData = []): void;
}
