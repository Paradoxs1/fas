<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


/**
 * @ORM\Table(name="accounting_position")
 * @ORM\Entity(repositoryClass="App\Repository\AccountingPositionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class AccountingPosition
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AccountingCategory")
     * @ORM\JoinColumn(nullable=false)
     */
    private $accountingCategory;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FacilityLayout", inversedBy="accountingPositions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $facilityLayout;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(nullable=true)
     */
    private $currency = null;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    private $sequence;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default" : 0})
     */
    private $predefined;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FlexParam", mappedBy="accountingPosition", cascade={"remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $flexParams;

    /**
     * AccountingPosition constructor.
     */
    public function __construct()
    {
        $this->flexParams = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AccountingCategory|null
     */
    public function getAccountingCategory(): ?AccountingCategory
    {
        return $this->accountingCategory;
    }

    /**
     * @param AccountingCategory|null $accountingCategory
     * @return AccountingPosition
     */
    public function setAccountingCategory(?AccountingCategory $accountingCategory): self
    {
        $this->accountingCategory = $accountingCategory;

        return $this;
    }

    /**
     * @return FacilityLayout|null
     */
    public function getFacilityLayout(): ?FacilityLayout
    {
        return $this->facilityLayout;
    }

    /**
     * @param FacilityLayout|null $facilityLayout
     * @return AccountingPosition
     */
    public function setFacilityLayout(?FacilityLayout $facilityLayout): self
    {
        $this->facilityLayout = $facilityLayout;

        return $this;
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency $currency
     * @return AccountingPosition
     */
    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @param mixed $sequence
     * @return AccountingCategory
     */
    public function setSequence($sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     * @return AccountingCategory
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

    /**
     * @ORM\PreUpdate
     * @return AccountingCategory
     */
    public function setModifiedAt(): self
    {
        $this->modifiedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTimeInterface|null $deletedAt
     * @return AccountingPosition
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFlexParams()
    {
        return $this->flexParams;
    }

//    /**
//     * @param mixed $flexParams
//     */
//    public function setFlexParams($flexParams): void
//    {
//        $this->flexParams = $flexParams;
//    }

    /**
     * @return mixed
     */
    public function getPredefined()
    {
        return $this->predefined;
    }

    /**
     * @param mixed $predefined
     */
    public function setPredefined($predefined): void
    {
        $this->predefined = $predefined;
    }
}
