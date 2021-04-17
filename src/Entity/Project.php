<?php

namespace App\Entity;

use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 * @ORM\Table(name="project")
 */
class Project
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(name="project_id", type="string", length=36, nullable=false)
     */
    protected string $id;

    /**
     * @ORM\Column(name="project_code", type="string", length=12, nullable=false, unique=true)
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=12)
     */
    protected string $code;

    /**
     * @ORM\Column(name="project_name", type="string", length=32, nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=32)
     */
    protected string $name;

    /**
     * @ORM\Column(name="project_description", type="string", length=128, nullable=true)
     *
     * @Assert\Length(max=128)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="project_repository", type="string", length=192, nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @Assert\Length(max=192)
     */
    protected string $repository;

    /**
     * @ORM\Column(name="project_repository_ssh_key", type="text", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    protected ?string $repositorySSHKey = null;

    /**
     * @ORM\Column(name="project_config", type="text", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @AppAssert\ProjectConfig()
     */
    protected ?string $config = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Environment", mappedBy="project")
     */
    protected Collection $environments;

    public function __construct()
    {
        $this->environments = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    public function getRepositorySSHKey(): ?string
    {
        return $this->repositorySSHKey;
    }

    public function setRepositorySSHKey(?string $repositorySSHKey): self
    {
        $this->repositorySSHKey = $repositorySSHKey;
        return $this;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function setConfig(?string $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(): Collection
    {
        return $this->environments;
    }

    public function addEnvironment(Environment $environment): self
    {
        $this->environments->add($environment);
        return $this;
    }

    public function removeEnvironment(Environment $environment): self
    {
        $this->environments->remove($environment);
        return $this;
    }
}