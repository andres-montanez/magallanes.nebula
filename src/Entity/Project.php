<?php

namespace App\Entity;

use App\Validator as AppAssert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\ProjectRepository')]
#[ORM\Table(name: 'project')]
final class Project
{
    #[ORM\Id()]
    #[ORM\Column(name: 'project_id', type: 'string', length: 32, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['project-list', 'project-detail'])]
    private string $id;

    #[ORM\Column(name: 'project_code', type: 'string', length: 12, nullable: false, unique: true)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 3, max: 12)]
    #[Groups(['project-list', 'project-detail'])]
    private string $code;

    #[ORM\Column(name: 'project_name', type: 'string', length: 32, nullable: false)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 3, max: 32)]
    #[Groups(['project-list', 'project-detail'])]
    private string $name;

    #[ORM\Column(name: 'project_description', type: 'string', length: 128, nullable: true)]
    #[Assert\Length(max: 128)]
    #[Groups(['project-list', 'project-detail'])]
    private ?string $description = null;

    #[ORM\Column(name: 'project_repository', type: 'string', length: 192, nullable: false)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 192)]
    #[Groups(['project-detail'])]
    private string $repository;

    #[ORM\Column(name: 'project_repository_ssh_private_key', type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Ignore]
    private ?string $repositorySSHPrivateKey = null;

    #[ORM\Column(name: 'project_repository_ssh_public_key', type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['project-detail'])]
    private ?string $repositorySSHPublicKey = null;

    #[ORM\Column(name: 'project_config', type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[AppAssert\ProjectConfig()]
    #[Groups(['project-detail'])]
    private ?string $config = null;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Environment', mappedBy: 'project')]
    #[Ignore]
    private Collection $environments;

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

    public function getRepositorySSHPrivateKey(): ?string
    {
        return $this->repositorySSHPrivateKey;
    }

    public function setRepositorySSHPrivateKey(?string $repositorySSHPrivateKey): self
    {
        $this->repositorySSHPrivateKey = $repositorySSHPrivateKey;
        return $this;
    }

    public function getRepositorySSHPublicKey(): ?string
    {
        return $this->repositorySSHPublicKey;
    }

    public function setRepositorySSHPublicKey(?string $repositorySSHPublicKey): self
    {
        $this->repositorySSHPublicKey = $repositorySSHPublicKey;
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
