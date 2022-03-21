<?php

namespace App\Entity;

use App\Library\Build\Config;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="build",
 *    uniqueConstraints={@ORM\UniqueConstraint(columns={"build_environment", "build_number"})}
 * )
 */
class Build
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CHECKING_OUT = 'checking-out';
    public const STATUS_CHECKED_OUT = 'checked-out';
    public const STATUS_CHECKOUT_FAILED = 'checkout-failed';
    public const STATUS_BUILDING = 'building';
    public const STATUS_BUILT = 'built';
    public const STATUS_PACKAGING = 'packaging';
    public const STATUS_PACKAGED = 'packaged';
    public const STATUS_RELEASING = 'releasing';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLBACK = 'rollback';
    public const STATUS_ROLLBACKING = 'rollbacking';
    public const STATUS_DELETE = 'delete';

    /**
     * @ORM\Id()
     * @ORM\Column(name="build_id", type="string", length="32", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     */
    protected ?string $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Environment")
     * @ORM\JoinColumn(name="build_environment", referencedColumnName="environment_id", nullable=false)
     *
     * @Assert\NotNull()
     */
    protected Environment $environment;

    /**
     * @ORM\Column(name="build_number", type="integer", nullable=false)
     *
     * @Assert\NotNull()
     */
    protected int $number;

    /**
     * @ORM\Column(name="build_rollback_number", type="integer", nullable=true)
     */
    protected ?int $rollbackNumber = null;

    /**
     * @ORM\Column(name="build_created_at", type="datetime_immutable", nullable=false)
     *
     * @Assert\NotNull()
     * @Assert\Date()
     */
    protected \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(name="build_started_at", type="datetime_immutable", nullable=true)
     *
     * @Assert\Date()
     */
    protected ?\DateTimeImmutable $startedAt = null;

    /**
     * @ORM\Column(name="build_finished_at", type="datetime_immutable", nullable=true)
     *
     * @Assert\Date()
     */
    protected ?\DateTimeImmutable $finishedAt = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BuildStage", mappedBy="build", cascade={"persist", "remove"})
     */
    protected Collection $stages;

    /**
     * @ORM\Column(name="build_branch", type="string", length=128, nullable=false)
     */
    protected string $branch;

    /**
     * @ORM\Column(name="build_commit_hash", type="string", length=40, nullable=true)
     */
    protected ?string $commitHash = null;

    /**
     * @ORM\Column(name="build_commit_message", type="text",nullable=true)
     */
    protected ?string $commitMessage = null;

    /**
     * @ORM\Column(name="build_checkout_std_out", type="text", nullable=true)
     */
    protected ?string $checkoutStdOut = null;

    /**
     * @ORM\Column(name="build_checkout_std_err", type="text", nullable=true)
     */
    protected ?string $checkoutStdErr = null;

    /**
     * @ORM\Column(name="build_requested_by", type="string", length=128, nullable=true)
     */
    protected ?string $requestedBy = null;

    /**
     * @ORM\Column(name="build_status", type="string", length=12, nullable=false)
     */
    protected string $status = self::STATUS_PENDING;

    private ?Config $config = null;

    public function __construct()
    {
        $this->stages = new ArrayCollection();
    }

    public function getConfig(): Config
    {
        if (!$this->config instanceof Config) {
            $this->config = new Config($this);
        }

        return $this->config;
    }

    public function isSuccessful(): bool
    {
        return $this->getStatus() === self::STATUS_SUCCESSFUL;
    }

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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function setEnvironment(Environment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getRollbackNumber(): ?int
    {
        return $this->rollbackNumber;
    }

    public function setRollbackNumber(int $rollbackNumber): self
    {
        $this->rollbackNumber = $rollbackNumber;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
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

    /**
     * @return BuildStage[]
     */
    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function addStage(BuildStage $stage): self
    {
        $this->stages->add($stage);
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

    public function getCommitHash(): ?string
    {
        return $this->commitHash;
    }

    public function setCommitHash(?string $commitHash): self
    {
        $this->commitHash = $commitHash;
        return $this;
    }

    public function getCommitShortHash(): string
    {
        return substr($this->getCommitHash(), 0, 7);
    }

    public function getCheckoutStdOut(): ?string
    {
        return $this->checkoutStdOut;
    }

    public function getCommitMessage(): ?string
    {
        return $this->commitMessage;
    }

    public function setCommitMessage(?string $commitMessage): self
    {
        $this->commitMessage = $commitMessage;
        return $this;
    }

    public function setCheckoutStdOut(?string $checkoutStdOut): self
    {
        $this->checkoutStdOut = $checkoutStdOut;
        return $this;
    }

    public function appendCheckoutStdOut(?string $checkoutStdOut): self
    {
        $this->checkoutStdOut = $this->checkoutStdOut . PHP_EOL . $checkoutStdOut;
        return $this;
    }

    public function getCheckoutStdErr(): ?string
    {
        return $this->checkoutStdErr;
    }

    public function setCheckoutStdErr(?string $checkoutStdErr): self
    {
        $this->checkoutStdErr = $checkoutStdErr;
        return $this;
    }

    public function appendCheckoutStdErr(?string $checkoutStdErr): self
    {
        $this->checkoutStdErr = $this->checkoutStdErr . PHP_EOL . $checkoutStdErr;
        return $this;
    }

    public function getRequestedBy(): ?string
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?string $requestedBy): self
    {
        $this->requestedBy = $requestedBy;
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
