<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'build_stage')]
class BuildStage
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(name: 'stage_id', type: 'integer', nullable: false)]
    #[Groups(['build-detail'])]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Build', inversedBy: 'stages')]
    #[ORM\JoinColumn(name: 'stage_build', referencedColumnName: 'build_id', nullable: false)]
    #[Assert\NotNull()]
    protected Build $build;

    #[ORM\Column(name: 'stage_name', type: 'string', length: 32, nullable: false)]
    #[Groups(['build-detail'])]
    protected string $name;

    #[ORM\Column(name: 'stage_docker', type: 'string', length: 64, nullable: true)]
    #[Groups(['build-detail'])]
    protected ?string $docker = null;

    /** @var Collection<int, BuildStageStep> */
    #[ORM\OneToMany(targetEntity: 'App\Entity\BuildStageStep', mappedBy: 'stage', cascade: ['persist', 'remove'])]
    #[Groups(['build-detail'])]
    protected Collection $steps;

    #[Assert\NotNull()]
    #[Assert\Date()]
    #[ORM\Column(name: 'stage_started_at', type: 'datetime_immutable', nullable: true)]
    #[Groups(['build-detail'])]
    protected ?\DateTimeImmutable $startedAt = null;

    #[Assert\NotNull()]
    #[Assert\Date()]
    #[ORM\Column(name: 'stage_finished_at', type: 'datetime_immutable', nullable: true)]
    #[Groups(['build-detail'])]
    protected ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'stage_status', type: 'string', length: 12, nullable: false)]
    #[Groups(['build-detail'])]
    protected string $status = self::STATUS_PENDING;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    #[Groups(['build-detail'])]
    public function getElapsedSeconds(): ?int
    {
        if ($this->getStartedAt() instanceof \DateTimeInterface) {
            $againstTime = new \DateTimeImmutable('now');
            if ($this->getFinishedAt() instanceof \DateTimeInterface) {
                $againstTime = $this->getFinishedAt();
            }

            return $againstTime->getTimestamp() - $this->getStartedAt()->getTimestamp();
        }

        return null;
    }

    #[Groups(['build-detail'])]
    public function getElapsedTime(): ?string
    {
        if ($this->getStartedAt() instanceof \DateTimeInterface) {
            $seconds = $this->getElapsedSeconds();
            if ($seconds <= 60) {
                return sprintf('%d seconds', $seconds);
            }

            return sprintf('%dmin %dsec', (floor($seconds / 60)), $seconds - (floor($seconds / 60) * 60));
        }

        return null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBuild(): Build
    {
        return $this->build;
    }

    public function setBuild(Build $build): self
    {
        $this->build = $build;
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

    public function getDocker(): ?string
    {
        return $this->docker;
    }

    public function setDocker(?string $docker): self
    {
        $this->docker = $docker;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    /** @return Collection<int, BuildStageStep> */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(BuildStageStep $step): self
    {
        $this->steps->add($step);
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
}
