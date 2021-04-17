<?php

namespace App\Entity;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="environment",
 *    uniqueConstraints={@ORM\UniqueConstraint(columns={"environment_project", "environment_code"})}
 * )
 */
class Environment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="environment_id", type="string", length=36, nullable=false)
     */
    protected string $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="environments")
     * @ORM\JoinColumn(name="environment_project", referencedColumnName="project_id", nullable=false)
     *
     * @Assert\NotNull()
     */
    protected Project $project;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserGroup")
     * @ORM\JoinColumn(name="environment_group", referencedColumnName="group_id", nullable=false)
     *
     * @Assert\NotNull()
     */
    protected UserGroup $group;

    /**
     * @ORM\Column(name="environment_code", type="string", length=12, nullable=false)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=12)
     */
    protected string $code;

    /**
     * @ORM\Column(name="environment_name", type="string", length=32, nullable=false)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=32)
     */
    protected string $name;

    /**
     * @ORM\Column(name="environment_branch", type="string", length=128, nullable=false)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(max=128)
     */
    protected string $branch;

    /**
     * @ORM\Column(name="environment_config", type="text", nullable=false)
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @AppAssert\EnvironmentConfig()
     */
    protected string $config;

    /**
     * @ORM\Column(name="environment_ssh_key", type="text", nullable=true)
     * @Assert\NotBlank(allowNull=true)
     */
    protected ?string $sshKey = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getGroup(): UserGroup
    {
        return $this->group;
    }

    public function setGroup(UserGroup $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function setBranch(string $branch): self
    {
        $this->branch = $branch;
        return $this;
    }

    public function getConfig(): string
    {
        return $this->config;
    }

    public function setConfig(string $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function getSSHKey(): ?string
    {
        return $this->sshKey;
    }

    public function setSSHKey(?string $sshKey): self
    {
        $this->sshKey = $sshKey;
        return $this;
    }
}