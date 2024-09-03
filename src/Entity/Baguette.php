<?php

namespace App\Entity;

use App\Repository\BaguetteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BaguetteRepository::class)]
class Baguette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Bois = null;

    #[ORM\Column(length: 255)]
    private ?string $Coeur = null;

    #[ORM\Column]
    private ?float $Taille = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBois(): ?string
    {
        return $this->Bois;
    }

    public function setBois(string $Bois): static
    {
        $this->Bois = $Bois;

        return $this;
    }

    public function getCoeur(): ?string
    {
        return $this->Coeur;
    }

    public function setCoeur(string $Coeur): static
    {
        $this->Coeur = $Coeur;

        return $this;
    }

    public function getTaille(): ?float
    {
        return $this->Taille;
    }

    public function setTaille(float $Taille): static
    {
        $this->Taille = $Taille;

        return $this;
    }
}
