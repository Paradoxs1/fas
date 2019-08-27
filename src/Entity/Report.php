<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReportRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Report
{
    const REPORT_APPROVED = true;
    const REPORT_NOT_APPROVED = false;

    const REPORT_TYPE_CASHIER = 1;
    const REPORT_TYPE_BACKOFFICER = 2;
    const REPORT_TYPE_MIGRATION = 3;

    public static $types = [
        self::REPORT_TYPE_CASHIER => 'cashier',
        self::REPORT_TYPE_BACKOFFICER => 'backofficer',
        self::REPORT_TYPE_MIGRATION => 'migration',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default" : 0})
     */
    private $approved;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shifts;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $number;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $statementDate;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\FacilityLayout", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $facilityLayout;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Account")
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ReportPosition", mappedBy="report", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $reportPositions;

    /**
     * Report Type
     *
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Report", cascade={"persist", "remove"})
     */
    private $parentReport;

    /**
     * Report constructor.
     */
    public function __construct()
    {
        $this->reportPositions = new ArrayCollection();
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
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
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * @param mixed $approved
     */
    public function setApproved($approved): void
    {
        $this->approved = $approved;
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
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number): void
    {
        $this->number = $number;
    }

    /**
     * @return \DateTime|null
     */
    public function getStatementDate(): ?\DateTime
    {
        return $this->statementDate;
    }

    /**
     * @param mixed $statementDate
     */
    public function setStatementDate($statementDate): void
    {
        $this->statementDate = $statementDate;
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
     * @return facilityLayout|null
     */
    public function getFacilityLayout(): ?FacilityLayout
    {
        return $this->facilityLayout;
    }

    /**
     * @param FacilityLayout|null $facilityLayout
     * @return Report
     */
    public function setFacilityLayout(?FacilityLayout $facilityLayout): self
    {
        $this->facilityLayout = $facilityLayout;

        return $this;
    }

    /**
     * @return Account
     */
    public function getCreatedBy(): Account
    {
        return $this->createdBy;
    }

    /**
     * @param Account $createdBy
     * @return Report
     */
    public function setCreatedBy(Account $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection|ReportPosition[]
     */
    public function getReportPositions(): Collection
    {
        return $this->reportPositions;
    }

    /**
     * @param ReportPosition $reportPosition
     * @return Report
     */
    public function addReportPosition(ReportPosition $reportPosition): self
    {
        if (!$this->reportPositions->contains($reportPosition)) {
            $this->reportPositions[] = $reportPosition;
            $reportPosition->setReport($this);
        }

        return $this;
    }

    /**
     * @param ReportPosition $reportPosition
     * @return Report
     */
    public function removeReportPosition(ReportPosition $reportPosition): self
    {
        if ($this->reportPositions->contains($reportPosition)) {
            $this->reportPositions->removeElement($reportPosition);
            // set the owning side to null (unless already changed)
            if ($reportPosition->getReport() === $this) {
                $reportPosition->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Report|null
     */
    public function getParentReport()
    {
        return $this->parentReport;
    }

    /**
     * @param Report $parentReport
     */
    public function setParentReport(Report $parentReport)
    {
        $this->parentReport = $parentReport;
    }
}
