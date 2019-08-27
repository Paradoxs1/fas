<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="account")
 * @ORM\Entity(repositoryClass="App\Repository\AccountRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields={"login", "deletedAt"},
 *     ignoreNull=false,
 *     message="security.login_uniq",
 *     groups={"add", "edit"}
 * )
 */
class Account implements UserInterface, \Serializable, EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(
     *     message = "security.login_not_blank", groups={"add", "edit"}
     * )
     * @Assert\Regex
     * (
     *     pattern="/^[a-zA-Z0-9\S]{2,}$/",
     *     message="security.login_min_length",
     *     groups={"add", "edit"}
     * )
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(
     *     message = "security.password_not_blank", groups={"add"}
     * )
     * @Assert\Length(
     *      min = 6,
     *      minMessage = "security.password_min_length",
     *      groups={"add"}
     * )
     */
    private $passwordHash;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\AccountEmail", cascade={"persist", "remove"})
     * @Assert\Valid(groups={"add", "edit"})
     */
    private $accountEmail;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $latestLoginAt;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $suspendedAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Account")
     */
    private $susupendedBy;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LoginAttempt", mappedBy="account")
     */
    private $loginAttempts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AccountFacilityRole", mappedBy="account", cascade={"persist", "remove"})
     */
    private $accountFacilityRoles;

    /**
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @Assert\Valid
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tenant")
     * @ORM\JoinColumn(nullable=true)
     */
    private $tenant;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $rolesChanged;

    /**
     * Random string sent to the user email address in order to verify the password resetting request
     *
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $passwordResetToken;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $passwordRequestedAt;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->login;
    }

    /**
     * Account constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->susupendedBy         = new ArrayCollection();
        $this->loginAttempts        = new ArrayCollection();
        $this->accountFacilityRoles = new ArrayCollection();
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
     * @return null|string
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return Account
     */
    public function setLogin(?string $login): self
    {
        $this->login = strtolower($login);

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    /**
     * @param null|string $passwordHash
     * @return Account
     */
    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    /**
     * @return AccountEmail|null
     */
    public function getAccountEmail(): ?AccountEmail
    {
        return $this->accountEmail;
    }

    /**
     * @param AccountEmail|null $accountEmail
     * @return Account
     */
    public function setAccountEmail(?AccountEmail $accountEmail): self
    {
        $this->accountEmail = $accountEmail;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLatestLoginAt(): ?\DateTimeInterface
    {
        return $this->latestLoginAt;
    }

    /**
     * @param \DateTimeInterface|null $latestLoginAt
     * @return Account
     */
    public function setLatestLoginAt(?\DateTimeInterface $latestLoginAt): self
    {
        $this->latestLoginAt = $latestLoginAt;

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
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTimeInterface|null $deletedAt
     * @return Account
     */
    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getSuspendedAt(): ?\DateTimeInterface
    {
        return $this->suspendedAt;
    }

    /**
     * @param \DateTimeInterface|null $suspendedAt
     * @return Account
     */
    public function setSuspendedAt(?\DateTimeInterface $suspendedAt): self
    {
        $this->suspendedAt = $suspendedAt;

        return $this;
    }

    /**
     * @return Collection|Account[]
     */
    public function getSusupendedBy(): Collection
    {
        return $this->susupendedBy;
    }

    /**
     * @param Account $susupendedBy
     * @return Account
     */
    public function addSusupendedBy(Account $susupendedBy): self
    {
        if (!$this->susupendedBy->contains($susupendedBy)) {
            $this->susupendedBy[] = $susupendedBy;
        }

        return $this;
    }

    /**
     * @param Account $susupendedBy
     * @return Account
     */
    public function removeSusupendedBy(Account $susupendedBy): self
    {
        if ($this->susupendedBy->contains($susupendedBy)) {
            $this->susupendedBy->removeElement($susupendedBy);
        }

        return $this;
    }

    /**
     * @return Collection|LoginAttempt[]
     */
    public function getLoginAttempts(): Collection
    {
        return $this->loginAttempts;
    }

    /**
     * @param LoginAttempt $loginAttempt
     * @return Account
     */
    public function addLoginAttempt(LoginAttempt $loginAttempt): self
    {
        if (!$this->loginAttempts->contains($loginAttempt)) {
            $this->loginAttempts[] = $loginAttempt;
            $loginAttempt->setAccount($this);
        }

        return $this;
    }

    /**
     * @param LoginAttempt $loginAttempt
     * @return Account
     */
    public function removeLoginAttempt(LoginAttempt $loginAttempt): self
    {
        if ($this->loginAttempts->contains($loginAttempt)) {
            $this->loginAttempts->removeElement($loginAttempt);
            // set the owning side to null (unless already changed)
            if ($loginAttempt->getAccount() === $this) {
                $loginAttempt->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AccountFacilityRole[]
     */
    public function getAccountFacilityRoles(): Collection
    {
        return $this->accountFacilityRoles;
    }

    /**
     * @return array
     */
    public function getAccountFacilities(): array
    {
        $result = [];

        if ($this->accountFacilityRoles) {
            foreach ($this->accountFacilityRoles as $afr) {
                if ($facility = $afr->getFacility()) {
                    $result[$facility->getId()] = $facility;
                }
            }
        }

        return $result;
    }

    /**
     * @param int $faciltyId
     * @param string $roleName
     * @return bool
     */
    public function hasFacilityRole(int $faciltyId, string $roleName)
    {
        $data = $this->getAccountFacilityRoles();

        foreach ($data as $accountFacilityRole) {
            if (
                $accountFacilityRole->getFacility() &&
                $accountFacilityRole->getFacility()->getId() == $faciltyId &&
                $accountFacilityRole->getRole()->getInternalName() == $roleName
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param AccountFacilityRole $accountFacilityRole
     * @return Account
     */
    public function addAccountFacilityRole(AccountFacilityRole $accountFacilityRole): self
    {
        if (!$this->accountFacilityRoles->contains($accountFacilityRole)) {
            $this->accountFacilityRoles[] = $accountFacilityRole;
            $accountFacilityRole->setAccount($this);
        }

        return $this;
    }

    /**
     * @param AccountFacilityRole $accountFacilityRole
     * @return Account
     */
    public function removeAccountFacilityRole(AccountFacilityRole $accountFacilityRole): self
    {
        if ($this->accountFacilityRoles->contains($accountFacilityRole)) {
            $this->accountFacilityRoles->removeElement($accountFacilityRole);
            // set the owning side to null (unless already changed)
            if ($accountFacilityRole->getAccount() === $this) {
                $accountFacilityRole->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->login;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(
            [
                $this->id,
                $this->login,
                $this->passwordHash,
            ]
        );
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->login,
            $this->passwordHash,
        ) = unserialize($serialized);
    }

    /**
     *
     */
    public function eraseCredentials()
    {
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->getPasswordHash();
    }

    /**
     * @param string $passwordHash
     * @return Account
     */
    public function setPassword(string $passwordHash): self
    {
        return $this->setPasswordHash($passwordHash);
    }

    /**
     * @return AccountFacilityRole[]|Collection
     */
    public function getRoles()
    {
        $result = [];

        $roles = $this->getAccountFacilityRoles();

        foreach ($roles as $role) {
            $name = $role->getRole()->getInternalName();
            $result[$name] = $name;
        }

        return $result;
    }

    /**
     * @return null|string
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @return Person|null
     */
    public function getPerson(): ?Person
    {
        return $this->person;
    }

    /**
     * @param Person|null $person
     * @return Account
     */
    public function setPerson(?Person $person): self
    {
        $this->person = $person;

        return $this;
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
     * @return Account
     */
    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * Return full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->person->getFirstName().' '.$this->person->getLastName();
    }

    /**
     * Gets triggered only on insert

     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt();
    }

    /**
     * Gets triggered every time on update

     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->setModifiedAt();
    }

    /**
     * @return mixed
     */
    public function getRolesChanged()
    {
        return $this->rolesChanged;
    }

    /**
     * @param mixed $rolesChanged
     */
    public function setRolesChanged($rolesChanged): void
    {
        $this->rolesChanged = $rolesChanged;
    }

    /**
     * @return null|string
     */
    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    /**
     * @param null|string $passwordResetToken
     */
    public function setPasswordResetToken(?string $passwordResetToken): void
    {
        $this->passwordResetToken = $passwordResetToken;
    }

    /**
     * @param \DateInterval $ttl
     * @return bool
     */
    public function isPasswordRequestNonExpired(\DateInterval $ttl): bool
    {
        if (null === $this->passwordRequestedAt) {
            return false;
        }

        $threshold = new \DateTime();
        $threshold->sub($ttl);

        return $threshold <= $this->passwordRequestedAt;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param \DateTimeInterface|null $date
     */
    public function setPasswordRequestedAt(?\DateTimeInterface $date): void
    {
        $this->passwordRequestedAt = $date;
    }
}
