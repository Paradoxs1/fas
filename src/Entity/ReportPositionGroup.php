<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReportPositionGroupRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ReportPositionGroup
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

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
     * ReportPosition constructor.
     */
    public function __construct()
    {
        $this->account = new ArrayCollection();
        $this->reportPositionValues = new ArrayCollection();
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

//    /**
//     * @return AccountingPosition|null
//     */
//    public function getAccountingPosition(): ?AccountingPosition
//    {
//        return $this->accountingPosition;
//    }
//
//    /**
//     * @param AccountingPosition|null $accountingPosition
//     * @return ReportPosition
//     */
//    public function setAccountingPosition(?AccountingPosition $accountingPosition): self
//    {
//        $this->accountingPosition = $accountingPosition;
//
//        return $this;
//    }

//    /**
//     * @return Collection|Account[]
//     */
//    public function getAccount(): Collection
//    {
//        return $this->account;
//    }
//
//    /**
//     * @param Account $account
//     * @return ReportPosition
//     */
//    public function addAccount(Account $account): self
//    {
//        if (!$this->account->contains($account)) {
//            $this->account[] = $account;
//        }
//
//        return $this;
//    }

//    /**
//     * @param Account $account
//     * @return ReportPosition
//     */
//    public function removeAccount(Account $account): self
//    {
//        if ($this->account->contains($account)) {
//            $this->account->removeElement($account);
//        }
//
//        return $this;
//    }
//
//    /**
//     * @return Report|null
//     */
//    public function getReport(): ?Report
//    {
//        return $this->report;
//    }
//
//    /**
//     * @param Report|null $report
//     * @return ReportPosition
//     */
//    public function setReport(?Report $report): self
//    {
//        $this->report = $report;
//
//        return $this;
//    }

    /**
     * @return Collection|ReportPositionValue[]
     */
    public function getReportPositionValues(): Collection
    {
        return $this->reportPositionValues;
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
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

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
     *  @ORM\PreUpdate
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
     * @return Report
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @param ReportPositionValue $reportPositionValue
     * @return ReportPosition
     */
    public function addReportPositionValue(ReportPositionValue $reportPositionValue): self
    {
        if (!$this->reportPositionValues->contains($reportPositionValue)) {
            $this->reportPositionValues[] = $reportPositionValue;
            $reportPositionValue->setReportPosition($this);
        }

        return $this;
    }

    /**
     * @param ReportPositionValue $reportPositionValue
     * @return ReportPosition
     */
    public function removeReportPositionValue(ReportPositionValue $reportPositionValue): self
    {
        if ($this->reportPositionValues->contains($reportPositionValue)) {
            $this->reportPositionValues->removeElement($reportPositionValue);
            // set the owning side to null (unless already changed)
            if ($reportPositionValue->getReportPosition() === $this) {
                $reportPositionValue->setReportPosition(null);
            }
        }

        return $this;
    }
}
