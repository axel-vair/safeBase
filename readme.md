# Projet Safebase

## Objectifs 

Le projet vise à développer une solution complète de gestion de la sauvegarde et de la restauration de bases de données sous forme d’une API REST. Cette solution devra répondre aux besoins suivants :

- [x] **Ajout de base de données** 
  - _Ajouter une connexion à une base de données_


- [x] **Automatisation des sauvegardes régulières**
  - _Planifier et effectuer des sauvegardes périodiques des bases de données, en utilisant le standard cron et les utilitaires système de MySQL et postgres._


- [x] **Gestion des versions**
  - _Conserver l’historique des différentes versions sauvegardées, avec des options pour choisir quelle version restaurer._


- [x] **Surveillance et alertes**
  - _Générer des alertes en cas de problème lors des processus de sauvegarde ou de restauration._


- [x] **Interface utilisateur** 
  - _Proposer une interface simple pour permettre aux utilisateurs de gérer facilement les processus de sauvegarde et de restauration._


- [ ] **Intégrations de tests**
  - _Écrire des tests fonctionnels permettant de s’assurer du bon fonctionnement de l’API, ainsi que la bonne exécution des sauvegardes et restaurations._


- [x] **Containérisation**
  - _Le projet devra être conteneurisé incluant l’API, une base MySQL, une base postgres, et le frontend s'il ne fait_
