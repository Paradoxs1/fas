<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="routine")
 * @ORM\Entity(repositoryClass="App\Repository\RoutineRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Routine
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
     * @ORM\ManyToOne(targetEntity="RoutineTemplate")
     * @ORM\JoinColumn(name="routine_template_id", referencedColumnName="id")
     */
    private $routineTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $params;

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

    public function getRoutineTemplate(): ?RoutineTemplate
    {
        return $this->routineTemplate;
    }

    public function setRoutineTemplate(?RoutineTemplate $routineTemplate)
    {
        $this->routineTemplate = $routineTemplate;
    }

    /**
     * @return null|string
     */
    public function getParams(): ?string
    {
        return $this->params;
    }

    /**
     * @param null|string $params
     */
    public function setParams(?string $params)
    {
        $this->params = $params;
    }
}
