<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CountryTranslationRepository")
 */
class CountryTranslation
{
    use ORMBehaviors\Translatable\Translation;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAdministrativeName();
    }

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $administrativeName;

    /**
     * @return null|string
     */
    public function getAdministrativeName(): ?string
    {
        return $this->administrativeName;
    }

    /**
     * @param string $administrativeName
     * @return CountryTranslation
     */
    public function setAdministrativeName(string $administrativeName): self
    {
        $this->administrativeName = $administrativeName;

        return $this;
    }
}
