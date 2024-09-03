<?php

namespace App\DataFixtures;

use App\Entity\Personnage;
use App\Entity\Baguette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture {
    public function load(ObjectManager $manager) {
        // Personnages
        $this->createPersonnage($manager, "Harry", "Potter", 17);
        $this->createPersonnage($manager, "Hermione", "Granger", 17);
        $this->createPersonnage($manager, "Ron", "Weasley", 17);
        $this->createPersonnage($manager, "Albus", "Dumbledore", 150);
        $this->createPersonnage($manager, "Severus", "Rogue", 38);
        $this->createPersonnage($manager, "Minerva", "McGonagall", 70);
        $this->createPersonnage($manager, "Rubeus", "Hagrid", 65);
        $this->createPersonnage($manager, "Sirius", "Black", 36);
        $this->createPersonnage($manager, "Remus", "Lupin", 37);
        $this->createPersonnage($manager, "Draco", "Malefoy", 16);
        $this->createPersonnage($manager, "Voldemort", "Jedusor", 71);
        $this->createPersonnage($manager, "Ginny", "Weasley", 16);
        $this->createPersonnage($manager, "Neville", "Londubat", 17);
        $this->createPersonnage($manager, "Luna", "Lovegood", 16);
        $this->createPersonnage($manager, "Fred", "Weasley", 20);
        $this->createPersonnage($manager, "George", "Weasley", 20);
        $this->createPersonnage($manager, "Bellatrix", "Lestrange", 48);
        $this->createPersonnage($manager, "Cedric", "Diggory", 18);
        $this->createPersonnage($manager, "Dolores", "Ombrage", 63);
        $this->createPersonnage($manager, "Argus", "Rusard", 62);

        // Baguettes
        $this->createBaguette($manager, "Houx", "Plume de phénix", 30.5);
        $this->createBaguette($manager, "Chêne", "Crin de licorne", 32.0);
        $this->createBaguette($manager, "Saule cogneur", "Ventricule de dragon", 38.1);
        $this->createBaguette($manager, "Bouleau", "Plume de phénix", 28.7);
        $this->createBaguette($manager, "Ébène", "Crin de licorne", 35.6);
        $this->createBaguette($manager, "Frêne", "Crin de licorne", 31.1);
        $this->createBaguette($manager, "Noyer", "Plume de phénix", 29.2);
        $this->createBaguette($manager, "Aubépine", "Crin de licorne", 24.5);
        $this->createBaguette($manager, "Cerisier", "Plume de phénix", 27.8);
        $this->createBaguette($manager, "Sycomore", "Ventricule de dragon", 33.4);
        $this->createBaguette($manager, "Châtaignier", "Crin de licorne", 30.3);
        $this->createBaguette($manager, "Érable", "Crin de licorne", 26.9);
        $this->createBaguette($manager, "Orme", "Crin de licorne", 35.1);
        $this->createBaguette($manager, "Charme", "Plume de phénix", 24.2);
        $this->createBaguette($manager, "Bouleau argenté", "Ventricule de dragon", 31.8);
        $this->createBaguette($manager, "Hêtre", "Crin de licorne", 29.5);
        $this->createBaguette($manager, "Cyprès", "Plume de phénix", 32.6);
        $this->createBaguette($manager, "Épicéa", "Crin de licorne", 27.7);
        $this->createBaguette($manager, "Chêne blanc", "Ventricule de dragon", 34.2);
        $this->createBaguette($manager, "Bouleau verruqueux", "Crin de licorne", 28.3);

        $manager->flush();
    }

    private function createPersonnage(ObjectManager $manager, string $nom, string $prenom, int $age) {
        $personnage = new Personnage();
        $personnage->setNom($nom);
        $personnage->setPrenom($prenom);
        $personnage->setAge($age);
        $manager->persist($personnage);
    }

    private function createBaguette(ObjectManager $manager, string $bois, string $coeur, float $taille) {
        $baguette = new Baguette();
        $baguette->setBois($bois);
        $baguette->setCoeur($coeur);
        $baguette->setTaille($taille);
        $manager->persist($baguette);
    }
}
