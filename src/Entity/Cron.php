<?php

namespace App\Entity;

use App\Repository\CronRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CronRepository::class)]
class Cron
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $backupFrequency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBackupFrequency(): ?string
    {
        return $this->backupFrequency;
    }

    public function setBackupFrequency(string $backupFrequency): static
    {
        $this->backupFrequency = $backupFrequency;

        return $this;
    }
}
