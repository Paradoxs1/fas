<?php

namespace App\Repository;

interface LoggerRepositoryInterface
{
    public function getEntityArrayResult(string $name, $value): array;
}