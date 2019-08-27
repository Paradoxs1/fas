<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FacilityLayoutRepository")
 */
class FacilityLayout
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $shifts;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Facility", inversedBy="facilityLayouts")
     * @ORM\JoinColumn(nullable=true)
     */
    private $facility;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     * @ORM\JoinColumn(nullable=true)
     */
    private $currency;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AccountingPosition", mappedBy="facilityLayout", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $accountingPositions;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $daysInPast;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tenant")
     * @ORM\JoinColumn(name="tenant_id", referencedColumnName="id")
     */
    private $tenant;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paymentMethodOrder;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * FacilityLayout constructor.
     */
    public function __construct()
    {
        $this->accountingPositions = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getShifts()
    {
        return $this->shifts;
    }

    /**
     * @param mixed $shifts
     */
    public function setShifts($shifts): void
    {
        $this->shifts = $shifts;
    }

    /**
     * @return Facility|null
     */
    public function getFacility(): ?Facility
    {
        return $this->facility;
    }

    /**
     * @param Facility $facility
     * @return FacilityLayout
     */
    public function setFacility(?Facility $facility): self
    {
        $this->facility = $facility;

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
     * @return FacilityLayout
     */
    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDaysInPast()
    {
        return $this->daysInPast;
    }

    /**
     * @param mixed daysInPast
     */
    public function setDaysInPast($daysInPast): void
    {
        $daysInPast = ($daysInPast < 0) ? $daysInPast * -1 : $daysInPast;

        $this->daysInPast = (int) $daysInPast;
    }

    /**
     * @return Tenant|null
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * @param Tenant|null $tenant
     * @return Facility
     */
    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * @return Collection|AccountingPosition[]
     */
    public function getAccountingPositions(): Collection
    {
        return $this->accountingPositions;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethodOrder()
    {
        return $this->paymentMethodOrder;
    }

    /**
     * @param mixed $paymentMethodOrder
     */
    public function setPaymentMethodOrder($paymentMethodOrder): void
    {
        $this->paymentMethodOrder = $paymentMethodOrder;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

    /**
     * @param \DateTimeInterface|null $modifiedAt
     * @return Facility
     */
    public function setModifiedAt(?\DateTimeInterface $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * @param AccountingPosition $accountingPosition
     * @return FacilityLayout
     */
    public function addAccountingPosition(AccountingPosition $accountingPosition): self
    {
        if (!$this->accountingPositions->contains($accountingPosition)) {
            $this->accountingPositions[] = $accountingPosition;
            $accountingPosition->setFacilityLayout($this);
        }

        return $this;
    }

    /**
     * @param AccountingPosition $accountingPosition
     * @return FacilityLayout
     */
    public function removeAccountingPosition(AccountingPosition $accountingPosition): self
    {
        if ($this->accountingPositions->contains($accountingPosition)) {
            $this->accountingPositions->removeElement($accountingPosition);
            // set the owning side to null (unless already changed)
            if ($accountingPosition->getFacilityLayout() === $this) {
                $accountingPosition->setFacilityLayout(null);
            }
        }

        return $this;
    }
}
