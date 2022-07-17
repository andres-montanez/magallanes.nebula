<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'build_stage_step')]
class BuildStageStep
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const TYPE_CMD = 'cmd';

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(name: 'step_id', type: 'integer', nullable: false)]
    #[Groups(['build-detail'])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\BuildStage', inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'step_stage', referencedColumnName: 'stage_id', nullable: false)]
    #[Assert\NotNull()]
    protected BuildStage $stage;

    #[ORM\Column(name: 'step_type', type: 'string', length: 12, nullable: false)]
    #[Groups(['build-detail'])]
    protected string $type;

    #[ORM\Column(name: 'step_definition', type: 'text', nullable: false)]
    #[Groups(['build-detail'])]
    protected string $definition;

    #[ORM\Column(name: 'step_time', type: 'integer', nullable: false)]
    #[Groups(['build-detail'])]
    protected int $time = 0;

    #[ORM\Column(name: 'step_status', type: 'string', length: 12, nullable: false)]
    #[Groups(['build-detail'])]
    protected string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'step_std_out', type: 'text', nullable: true)]
    #[Groups(['build-detail'])]
    protected ?string $stdOut = null;

    #[ORM\Column(name: 'step_std_err', type: 'text', nullable: true)]
    #[Groups(['build-detail'])]
    protected ?string $stdErr = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStage(): BuildStage
    {
        return $this->stage;
    }

    public function setStage(BuildStage $stage): self
    {
        $this->stage = $stage;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function setDefinition(string $definition): self
    {
        $this->definition = $definition;
        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStdOut(): ?string
    {
        return $this->stdOut;
    }

    public function setStdOut(?string $stdOut): self
    {
        $this->stdOut = $stdOut;
        return $this;
    }

    public function getStdErr(): ?string
    {
        return $this->stdErr;
    }

    public function setStdErr(?string $stdErr): self
    {
        $this->stdErr = $stdErr;
        return $this;
    }
}
