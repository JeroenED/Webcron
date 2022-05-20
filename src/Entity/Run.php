<?php

namespace App\Entity;

use App\Repository\RunRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as CollectionAlias;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 */
#[ORM\Entity(repositoryClass: RunRepository::class)]
class Run
{
    /**
     * @var int|null
     */
    #[ORM\Id()]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id;

    /**
     * @var Job
     * @ORM\Column(type="string")
     */
    #[ORM\ManyToOne(targetEntity: "App\Entity\Job", inversedBy: "runs")]
    #[ORM\JoinColumn(name: "job_id", referencedColumnName: "id")]
    private Job $job;

    /**
     * @var string
     */
    #[ORM\Column(type: "string", length: 15)]
    private string $exitcode;

    /**
     * @var string
     */
    #[ORM\Column(type: "text")]
    private string $output;

    /**
     * @var float
     */
    #[ORM\Column(type: "float")]
    private float $runtime;

    /**
     * @var int
     */
    #[ORM\Column(type: "integer")]
    private int $timestamp;

    /**
     * @var string
     */
    #[ORM\Column(type: "string", length: 5)]
    private string $flags;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Run
     */
    public function setId(?int $id): Run
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * @param Job $job
     * @return Run
     */
    public function setJob(Job $job): Run
    {
        $this->job = $job;
        return $this;
    }

    /**
     * @return string
     */
    public function getExitcode(): string
    {
        return $this->exitcode;
    }

    /**
     * @param string $exitcode
     * @return Run
     */
    public function setExitcode(string $exitcode): Run
    {
        $this->exitcode = $exitcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @param string $output
     * @return Run
     */
    public function setOutput(string $output): Run
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return float
     */
    public function getRuntime(): float
    {
        return $this->runtime;
    }

    /**
     * @param float $runtime
     * @return Run
     */
    public function setRuntime(float $runtime): Run
    {
        $this->runtime = $runtime;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     * @return Run
     */
    public function setTimestamp(int $timestamp): Run
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return string
     */
    public function getFlags(): string
    {
        return $this->flags;
    }

    /**
     * @param string $flags
     * @return Run
     */
    public function setFlags(string $flags): Run
    {
        $this->flags = $flags;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'exitcode' => $this->getExitcode(),
            'output' => $this->getOutput(),
            'runtime' => $this->getRuntime(),
            'timestamp' => $this->getTimestamp(),
            'flags' => $this->getFlags(),
            'job' => $this->getJob()->getId(),
        ];
    }
}