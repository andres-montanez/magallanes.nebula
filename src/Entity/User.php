<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface
{
    public const ROLE_ADMINISTRATOR = 'ROLE_ADMINISTRATOR';
    public const ROLE_USER = 'ROLE_USER';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @ORM\Column(name="user_username", type="string", length=128, nullable=false, unique=true)
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=128)
     */
    protected string $username;

    /**
     * @ORM\Column(name="user_password", type="string", length=128, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=128)
     */
    protected ?string $password = null;

    /**
     * @ORM\Column(name="user_name", type="string", length=64, nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=4, max=64)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(name="user_roles", type="json_array", nullable=false)
     *
     * @Assert\NotNull()
     */
    protected array $roles = [];

    /**
     * @ORM\ManyToMany(targetEntity="UserGroup")
     * @ORM\JoinTable(name="rel_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="user_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="group_id", unique=true)}
     * )
     */
    private Collection $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(UserGroup $group): self
    {
        $this->groups->add($group);
        return $this;
    }

    public function removeGroup(UserGroup $group): self
    {
        $this->groups->remove($group);
        return $this;
    }

    public function getGroupIds(): array
    {
        $ids = [];
        foreach ($this->groups as $group) {
            $ids[] = $group->getId();
        }

        return $ids;
    }
}
