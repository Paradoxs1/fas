<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="facility")
 * @ORM\Entity(repositoryClass="App\Repository\FacilityRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Facility implements EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(
     *     message = "facility.name_not_blank"
     * )
     * @Assert\Regex
     * (
     *     pattern="/[A-Za-z]{2,}/",
     *     message="security.last_name_min_length"
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $template;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Tenant", inversedBy="facilities")
     * @ORM\JoinColumn(nullable=false)
     */
    private $tenant;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Address",  cascade={"persist"})
     */
    private $address;

    /**
     * Keep this OrderBy like it is now, as needed for configuration page.
     *
     * @ORM\OneToMany(targetEntity="App\Entity\CostForecastWeekDay", mappedBy="facility")
     * @ORM\OrderBy({"dayOfWeek" = "ASC", "category" = "ASC"})
     */
    private $costForecastWeekDay;

    /**
     * Keep this OrderBy like as it is now, as needed for configuration page.
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FacilityLayout", mappedBy="facility", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $facilityLayouts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AccountFacilityRole", mappedBy="facility", cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $accountFacilityRole;

    /**
     * @ORM\OneToOne(targetEntity="Routine")
     * @ORM\JoinColumn(name="routine_id", referencedColumnName="id")
     */
    private $routine;

    /**
     * @ORM\Column(type="boolean", options={"default" : false}, nullable=true)
     */
    private $enableInterface;

    /**
     * Facility constructor.
     */
    public function __construct()
    {
        $this->costForecastWeekDay = new ArrayCollection();
        $this->facilityLayouts     = new ArrayCollection();
        $this->accountFacilityRole = new ArrayCollection();
    }

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
     * @return Facility
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
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
     * @return Facility
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getTemplate(): ?bool
    {
        return $this->template;
    }

    /**
     * @param bool $template
     * @return Facility
     */
    public function setTemplate(bool $template): self
    {
        $this->template = $template;

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
     * @param \DateTimeImmutable $createdAt
     * @return Facility
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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
     * @param \DateTimeInterface|null $modifiedAt
     * @return Facility
     */
    public function setModifiedAt(?\DateTimeInterface $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

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
     * @return Facility
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|Report[]
     */
    public function getFacilityLayouts(): Collection
    {
        return $this->facilityLayouts;
    }

    /**
     * @param Report $report
     * @return Facility
     */
    public function addFacilityLayouts(FacilityLayout $facilityLayout): self
    {
        if (!$this->facilityLayouts->contains($facilityLayout)) {
            $this->facilityLayouts[] = $facilityLayout;
            $facilityLayout->setFacility($this);
        }

        return $this;
    }

    /**
     * @return Collection|Report[]
     */
    public function getCostForecastWeekDay(): Collection
    {
        return $this->costForecastWeekDay;
    }

    /**
     * @return Collection|AccountFacilityRole[]
     */
    public function getAccountFacilityRole(): Collection
    {
        return $this->accountFacilityRole;
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
     * @return Address|null
     */
    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /**
     * @param Address|null $address
     * @return Facility
     */
    public function setAddress(?Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Routine|null
     */
    public function getRoutine(): ?Routine
    {
        return $this->routine;
    }

    /**
     * @param Routine|null $routine
     */
    public function setRoutine(?Routine $routine)
    {
        $this->routine = $routine;
    }

    /**
     * @return mixed
     */
    public function getEnableInterface()
    {
        return $this->enableInterface;
    }

    /**
     * @param bool|null $enableInterface
     */
    public function setEnableInterface(?bool $enableInterface)
    {
        $this->enableInterface = $enableInterface;
    }
}
