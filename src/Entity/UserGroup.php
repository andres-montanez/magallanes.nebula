<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_group")
 * )
 */
class UserGroup
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @ORM\Column(name="group_name", type="string", length=64, nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=64)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(name="group_description", type="string", length=128, nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=128)
     */
    protected ?string $description = null;

    public function getId(): int
    {
        return $this->id;
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

    public function setDescription(?string $description = null): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}