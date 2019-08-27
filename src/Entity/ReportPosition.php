<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReportPositionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ReportPosition
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AccountingPosition")
     * @ORM\JoinColumn(nullable=false)
     */
    private $accountingPosition;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Account")
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Account")
     */
    private $modifiedBy;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Report", inversedBy="reportPositions", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $report;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ReportPositionValue", mappedBy="reportPosition", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $reportPositionValues;

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
     * @ORM\ManyToOne(targetEntity="ReportPosition", cascade={"persist", "remove"})
     */
    private $parentReportPosition;

    /**
     * ReportPosition constructor.
     */
    public function __construct()
    {
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
     * @return AccountingPosition|null
     */
    public function getAccountingPosition(): ?AccountingPosition
    {
        return $this->accountingPosition;
    }

    /**
     * @param AccountingPosition|null $accountingPosition
     * @return ReportPosition
     */
    public function setAccountingPosition(?AccountingPosition $accountingPosition): self
    {
        $this->accountingPosition = $accountingPosition;

        return $this;
    }

    /**
     * @return Report|null
     */
    public function getReport(): ?Report
    {
        return $this->report;
    }

    /**
     * @param Report|null $report
     * @return ReportPosition
     */
    public function setReport(?Report $report): self
    {
        $this->report = $report;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return mixed
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * @param mixed $modifiedBy
     */
    public function setModifiedBy($modifiedBy): void
    {
        $this->modifiedBy = $modifiedBy;
    }

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

    /**
     * @return ReportPosition|null
     */
    public function getParentReportPosition()
    {
        return $this->parentReportPosition;
    }

    /**
     * @param int
     */
    public function setParentReportPosition(ReportPosition $parentReportPosition)
    {
        $this->parentReportPosition = $parentReportPosition;
    }
}
