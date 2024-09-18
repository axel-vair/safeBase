#!/bin/bash

# Configuration du dossier
BACKUP_DIR="/Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron"
DATE=$(date +"%Y%m%d_%H%M%S")

# Création du dossier pour s'assurer qu'il existe
mkdir -p $BACKUP_DIR

# Fonction pour sauvegarder une base de données PostgreSQL
backup_postgres() {
    DB_NAME=$1
    CONTAINER_NAME=$2
    FILENAME="${DB_NAME}_${DATE}.sql"

    echo "Sauvegarde de $DB_NAME..."
    docker exec -t $CONTAINER_NAME pg_dump -U user -d $DB_NAME > "$BACKUP_DIR/$FILENAME"
    echo "Sauvegarde de $DB_NAME terminée."
}

# Fonction pour sauvegarder une base de données MySQL
backup_mysql() {
    DB_NAME=$1
    CONTAINER_NAME=$2
    FILENAME="${DB_NAME}_${DATE}.sql"

    echo "Sauvegarde de $DB_NAME..."
    docker exec -t $CONTAINER_NAME mysqldump -u user --password=password $DB_NAME > "$BACKUP_DIR/$FILENAME"
    echo "Sauvegarde de $DB_NAME terminée."
}

# Sauvegarder chaque base de données
backup_postgres "backupinfo" "safebase-database-1"
backup_postgres "backup" "safebase-backup-1"
backup_postgres "backuptwo" "safebase-backuptwo-1"
backup_mysql "fixtures_db" "safebase-fixtures_db-1"

echo "Toutes les sauvegardes sont terminées."
