<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[ORM\Column(name: "`data`", type: "text")]
    private string $data;

    /**
     * @var int
     */
    #[ORM\Column(name: "`interval`", type: "integer")]
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
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: "App\Entity\Run", mappedBy: "job", cascade: ["remove"])]
    private Collection $runs;

    public function __construct()
    {
        $this->runs = new ArrayCollection();
    }

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

    public function getData(?string $name = ''): mixed
    {
        $data = json_decode($this->data, true);
        if(!empty($name)) {
            $names = explode('.', $name);
            foreach($names as $item) {
                if(!isset($data[$item])) {
                    return NULL;
                }
                $data = $data[$item];
            }
        }
        return $data;
    }

    public function setData(string $name, mixed $value): Job
    {
        $data = json_decode($this->data ?? '{}', true);
        if (!empty($name)) {
            $this->addDataItem($data, $name, $value);
        }
        $this->data = json_encode($data);
        return $this;
    }

    private function addDataItem(array &$data, array|string $name, mixed $value): bool
    {
        $names = is_array($name) ? $name : explode('.', $name);
        $current = $names[0];
        if(count($names) > 1) {
            unset($names[0]);
            if(!isset($data[$current])) $data[$current] = [];
            $this->addDataItem($data[$current], array_values($names), $value);
        } else {
            $data[$names[0]] = $value;
        }
        return true;
    }

    public function removeData(?string $name = ''): mixed
    {
        $data = json_decode($this->data, true);
        if (!empty($name)) {
            $this->removeDataItem($data, $name);
        }
        $this->data = json_encode($data);
        return $this;
    }

    private function removeDataItem(array &$data, array|string $name): bool
    {
        $names = is_array($name) ? $name : explode('.', $name);
        $current = $names[0];
        if(!isset($data[$current])) {
            return false;
        } elseif(is_array($data[$current]) && isset($names[1])) {
            unset($names[0]);
            $this->removeDataItem($data[$current], array_values($names));
        } else {
            if(isset($data[$names[0]])) unset($data[$names[0]]);
        }
        return true;
    }

    public function hasData($name): bool
    {
        return !empty($this->getData($name));
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
    public function setLastrun(?int $lastrun): Job
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

    /**
     * @return Collection
     */
    public function getRuns(): Collection
    {
        return $this->runs;
    }

    /**
     * @param Collection $runs
     * @return Job
     */
    public function setRuns(Collection $runs): Job
    {
        $this->runs = $runs;
        return $this;
    }

    public function getToken(): string
    {
        return $this->getData('hooktoken') ?? '';
    }

    public function deleteToken(): Job
    {
        $this->removeData('hooktoken');
        return $this;
    }

    public function addToken(): Job
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        $length = 32;

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        $this->setData('hooktoken', $randomString);
        return $this;
    }
}