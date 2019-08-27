<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RoleRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Role
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_TENANT_MANAGER = 'ROLE_TENANT_MANAGER';
    const ROLE_TENANT_USER = 'ROLE_TENANT_USER';
    const ROLE_FACILITY_STAKEHOLDER = 'ROLE_FACILITY_STAKEHOLDER';
    const ROLE_FACILITY_MANAGER = 'ROLE_FACILITY_MANAGER';
    const ROLE_FACILITY_USER = 'ROLE_FACILITY_USER';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $administrativeName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $internalName;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $displayType;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Permission", inversedBy="roles")
     */
    private $permissions;

    /**
     * @var array
     */
    public static $tenantRolesRelation = [
        self::ROLE_TENANT_MANAGER => [
            self::ROLE_TENANT_MANAGER,
            self::ROLE_TENANT_USER,
            self::ROLE_FACILITY_STAKEHOLDER,
            self::ROLE_FACILITY_MANAGER,
            self::ROLE_FACILITY_USER,
        ],
        self::ROLE_TENANT_USER => [
            self::ROLE_TENANT_USER,
            self::ROLE_FACILITY_MANAGER,
            self::ROLE_FACILITY_USER,
        ]
    ];

    /**
     * @var array
     */
    public static $facilityRolesRelation = [
        self::ROLE_TENANT_USER => [
            self::ROLE_FACILITY_MANAGER,
            self::ROLE_FACILITY_USER,
        ]
    ];

    /**
     * Role constructor.
     */
    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAdministrativeName(): ?string
    {
        return $this->administrativeName;
    }

    public function setAdministrativeName(string $administrativeName): self
    {
        $this->administrativeName = $administrativeName;

        return $this;
    }

    public function getInternalName(): ?string
    {
        return $this->internalName;
    }

    public function setInternalName(string $internalName): self
    {
        $this->internalName = $internalName;

        return $this;
    }

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

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|Permission[]
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        if ($this->permissions->contains($permission)) {
            $this->permissions->removeElement($permission);
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDisplayType(): ?string
    {
        return $this->displayType;
    }

    /**
     * @param string $displayType
     */
    public function setDisplayType(string $displayType)
    {
        $this->displayType = $displayType;
    }
}
