<?php

namespace App\Entity;

use App\Validator as AppAssert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'environment')]
#[ORM\UniqueConstraint(name: 'unq_environment_code', columns: ['environment_project', 'environment_code'])]
class Environment
{
    public const WEBHOOK_DISABLED = 'disabled';
    public const WEBHOOK_PUSH = 'push';
    public const WEBHOOK_RELEASE = 'release';
    private const WEBHOOK_OPTIONS = [
        self::WEBHOOK_DISABLED,
        self::WEBHOOK_PUSH,
        self::WEBHOOK_RELEASE,
    ];

    #[ORM\Id()]
    #[ORM\Column(name: 'environment_id', type: 'string', length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['environment-list', 'environment-detail', 'build-detail'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Project', inversedBy: 'environments')]
    #[ORM\JoinColumn(name: 'environment_project', referencedColumnName: 'project_id', nullable: false)]
    #[Assert\NotNull()]
    #[Groups(['environment-detail', 'environment-summary', 'build-detail'])]
    private Project $project;

    #[ORM\Column(name: 'environment_code', type: 'string', length: 12, nullable: false)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 3, max: 12)]
    #[Groups(['environment-list', 'environment-detail', 'build-detail'])]
    private string $code;

    #[ORM\Column(name: 'environment_name', type: 'string', length: 32, nullable: false)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 3, max: 32)]
    #[Groups(['environment-list', 'environment-detail', 'build-detail'])]
    private string $name;

    #[ORM\Column(name: 'environment_branch', type: 'string', length: 128, nullable: false)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 128)]
    #[Groups(['environment-detail'])]
    private string $branch;

    #[ORM\Column(name: 'environment_webhook', type: 'string', length: 12, nullable: false)]
    #[Assert\NotNull()]
    #[Assert\Choice(choices: self::WEBHOOK_OPTIONS)]
    #[Groups(['environment-detail'])]
    private string $webhook = self::WEBHOOK_DISABLED;

    #[ORM\Column(name: 'environment_config', type: 'text', nullable: false)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[AppAssert\EnvironmentConfig()]
    #[Groups(['environment-detail'])]
    private string $config;

    #[ORM\Column(name: 'environment_ssh_private_key', type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Ignore]
    private ?string $sshPrivateKey = null;

    #[ORM\Column(name: 'environment_ssh_public_key', type: 'text', nullable: true)]
    #[Assert\NotBlank(allowNull: true)]
    #[Groups(['environment-detail'])]
    private ?string $sshPublicKey = null;

    public function getId(): ?string
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

    public function getWebhook(): string
    {
        return $this->webhook;
    }

    public function setWebhook(string $webhook): self
    {
        $this->webhook = $webhook;
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

    public function getSSHPrivateKey(): ?string
    {
        return $this->sshPrivateKey;
    }

    public function setSSHPrivateKey(?string $sshPrivateKey): self
    {
        $this->sshPrivateKey = $sshPrivateKey;
        return $this;
    }

    public function getSSHPublicKey(): ?string
    {
        return $this->sshPublicKey;
    }

    public function setSSHPublicKey(?string $sshPublicKey): self
    {
        $this->sshPublicKey = $sshPublicKey;
        return $this;
    }
}
