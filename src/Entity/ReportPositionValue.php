<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReportPositionValueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ReportPositionValue
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
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FlexParam")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parameter;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Account")
     */
    private $modifiedBy;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ReportPosition", inversedBy="reportPositionValues", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $reportPosition;

    /**
     * @ORM\ManyToOne(targetEntity="ReportPositionValue", cascade={"persist", "remove"})
     */
    private $parentReportPositionValue;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ReportPositionGroup", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $reportPositionGroup;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    private $sequence;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return ReportPositionValue
     */
    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return FlexParam|null
     */
    public function getParameter(): ?FlexParam
    {
        return $this->parameter;
    }

    /**
     * @param FlexParam|null $parameter
     * @return ReportPositionValue
     */
    public function setParameter(?FlexParam $parameter): self
    {
        $this->parameter = $parameter;

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
     * @return \DateTimeImmutable|null
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTimeImmutable $deletedAt
     * @return ReportPositionValue
     */
    public function setDeletedAt(\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return ReportPosition|null
     */
    public function getReportPosition(): ?ReportPosition
    {
        return $this->reportPosition;
    }

    /**
     * @param ReportPosition|null $reportPosition
     * @return ReportPositionValue
     */
    public function setReportPosition(?ReportPosition $reportPosition): self
    {
        $this->reportPosition = $reportPosition;

        return $this;
    }

    /**
     * @return ReportPositionGroup|null
     */
    public function getReportPositionGroup()
    {
        return $this->reportPositionGroup;
    }

    /**
     * @param ReportPositionGroup|null $reportPositionGroup
     * @return ReportPositionGroup
     */
    public function setReportPositionGroup(?ReportPositionGroup $reportPositionGroup): self
    {
        $this->reportPositionGroup = $reportPositionGroup;

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
     * @return ReportPositionValue|null
     */
    public function getParentReportPositionValue()
    {
        return $this->parentReportPositionValue;
    }

    /**
     * @param ReportPositionValue $parentReportPositionValue
     */
    public function setParentReportPositionValue(ReportPositionValue $parentReportPositionValue)
    {
        $this->parentReportPositionValue = $parentReportPositionValue;
    }
}
