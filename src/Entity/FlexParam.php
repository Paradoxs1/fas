<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="flex_param")
 * @ORM\Entity(repositoryClass="App\Repository\FlexParamRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FlexParam
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
    private $key;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $view;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default" : 0})
     */
    private $sequence;

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
     * @ORM\OneToMany(targetEntity="App\Entity\FlexParamValue", mappedBy="flexParam")
     * @ORM\JoinColumn(nullable=true)
     */
    private $defaultValue;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AccountingPosition", inversedBy="flexParams")
     * @ORM\JoinColumn(nullable=false)
     */
    private $accountingPosition;

    /**
     * FlexParam constructor.
     */
    public function __construct()
    {
        $this->defaultValue = new ArrayCollection();
    }

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
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return FlexParam
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return FlexParam
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
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
     * @return FlexParam
     */
    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @param string $view
     * @return FlexParam
     */
    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return int
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * @param int $sequence
     * @return FlexParam
     */
    public function setSequence(int $sequence = 0): self
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
     * @return FlexParam
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|FlexParamValue[]
     */
    public function getDefaultValue(): Collection
    {
        return $this->defaultValue;
    }

    /**
     * @param FlexParamValue $defaultValue
     * @return FlexParam
     */
    public function addDefaultValue(FlexParamValue $defaultValue): self
    {
        if (!$this->defaultValue->contains($defaultValue)) {
            $this->defaultValue[] = $defaultValue;
            $defaultValue->setFlexParam($this);
        }

        return $this;
    }

    /**
     * @param FlexParamValue $defaultValue
     * @return FlexParam
     */
    public function removeDefaultValue(FlexParamValue $defaultValue): self
    {
        if ($this->defaultValue->contains($defaultValue)) {
            $this->defaultValue->removeElement($defaultValue);
            // set the owning side to null (unless already changed)
            if ($defaultValue->getFlexParam() === $this) {
                $defaultValue->setFlexParam(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccountingPosition()
    {
        return $this->accountingPosition;
    }

    /**
     * @param mixed AccountingPosition $accountingPosition
     */
    public function setAccountingPosition(AccountingPosition $accountingPosition): void
    {
        $this->accountingPosition = $accountingPosition;
    }
}
