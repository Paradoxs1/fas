<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="routine_template")
 * @ORM\Entity(repositoryClass="App\Repository\RoutineTemplateRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RoutineTemplate
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Regex
     * (
     *     pattern="/[A-Za-z]{2,}/"
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $paramTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $accountingPositionsTemplate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $class;

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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName(?string $name)
    {
        $this->name = $name;
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
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTimeImmutable;
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
     */
    public function setModifiedAt(?\DateTimeInterface $modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;
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
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return null|string
     */
    public function getParamTemplate(): ?string
    {
        return $this->paramTemplate;
    }

    /**
     * @param null|string $paramTemplate
     */
    public function setParamTemplate(?string $paramTemplate)
    {
        $this->paramTemplate = $paramTemplate;
    }

    /**
     * @return null|string
     */
    public function getAccountingPositionsTemplate(): ?string
    {
        return $this->accountingPositionsTemplate;
    }

    /**
     * @param null|string $accountingPositionsTemplate
     */
    public function setAccountingPositionsTemplate(?string $accountingPositionsTemplate)
    {
        $this->accountingPositionsTemplate = $accountingPositionsTemplate;
    }

    /**
     * @return null|string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param null|string $class
     */
    public function setClass(?string $class)
    {
        $this->class = $class;
    }
}
