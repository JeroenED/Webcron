<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
class Job
{
    /**
     * @var int|null
     */
    #[ORM\Id()]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id;

    /**
     * @var string
     */
    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    /**
     * @var string
     */
    #[ORM\Column(type: "text")]
    private string $data;

    /**
     * @var int
     */
    #[ORM\Column(type: "integer")]
    private int $interval;

    /**
     * @var int
     */
    #[ORM\Column(type: "integer")]
    private int $nextrun;

    /**
     * @var int
     */
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $lastrun;

    /**
     * @var int
     */
    #[ORM\Column(type: "integer")]
    private int $running;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Job
     */
    public function setId(?int $id): Job
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Job
     */
    public function setName(string $name): Job
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return json_decode($this->data, true);
    }

    /**
     * @param array $data
     * @return Job
     */
    public function setData(array $data): Job
    {
        $this->data = json_encode($data);
        return $this;
    }

    public function addData(string $name, mixed $value): Job
    {
        $data = json_decode($this->data, true);
        $data[$name] = $value;
        $this->data = json_encode($data);

        return $this;
    }

    /**
     * @return int
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * @param int $interval
     * @return Job
     */
    public function setInterval(int $interval): Job
    {
        $this->interval = $interval;
        return $this;
    }

    /**
     * @return int
     */
    public function getNextrun(): int
    {
        return $this->nextrun;
    }

    /**
     * @param int $nextrun
     * @return Job
     */
    public function setNextrun(int $nextrun): Job
    {
        $this->nextrun = $nextrun;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastrun(): ?int
    {
        return $this->lastrun;
    }

    /**
     * @param int $lastrun
     * @return Job
     */
    public function setLastrun(int $lastrun): Job
    {
        $this->lastrun = $lastrun;
        return $this;
    }

    /**
     * @return int
     */
    public function getRunning(): int
    {
        return $this->running;
    }

    /**
     * @param int $running
     * @return Job
     */
    public function setRunning(int $running): Job
    {
        $this->running = $running;
        return $this;
    }

}