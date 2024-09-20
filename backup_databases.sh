#!/bin/bash
export PATH=$PATH:/usr/local/bin
DOCKER_PATH="/usr/local/bin/docker"
BACKUP_DIR="/Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron"
LOG_FILE="$BACKUP_DIR/cron_backup.log"
DATE=$(date +"%Y%m%d_%H%M%S")

# Fonction de logging
log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

log "Début du script de sauvegarde"
log "PATH : $PATH"
log "Chemin Docker : $DOCKER_PATH"

# Vérification de l'existence de Docker
if [ ! -x "$DOCKER_PATH" ]; then
    log "Erreur : Docker n'est pas trouvé à $DOCKER_PATH"
    exit 1
fi

# Création du dossier pour s'assurer qu'il existe
mkdir -p "$BACKUP_DIR"

# Fonction pour sauvegarder une base de données PostgreSQL
backup_postgres() {
    DB_NAME=$1
    CONTAINER_NAME=$2
    FILENAME="${DB_NAME}_${DATE}.sql"

    log "Sauvegarde de $DB_NAME..."
    log "Exécution de la commande : $DOCKER_PATH exec \"$CONTAINER_NAME\" sh -c \"pg_dump -U user -d $DB_NAME\" > \"$BACKUP_DIR/$FILENAME\""
    if $DOCKER_PATH exec "$CONTAINER_NAME" sh -c "pg_dump -U user -d $DB_NAME" > "$BACKUP_DIR/$FILENAME"; then
        log "Sauvegarde de $DB_NAME terminée."
    else
        log "Erreur lors de la sauvegarde de $DB_NAME"
    fi
}

# Fonction pour sauvegarder une base de données MySQL
backup_mysql() {
    DB_NAME=$1
    CONTAINER_NAME=$2
    FILENAME="${DB_NAME}_${DATE}.sql"

    log "Sauvegarde de $DB_NAME..."
    log "Exécution de la commande : $DOCKER_PATH exec \"$CONTAINER_NAME\" sh -c \"mysqldump -u user --password=password $DB_NAME\" > \"$BACKUP_DIR/$FILENAME\""
    if $DOCKER_PATH exec "$CONTAINER_NAME" sh -c "mysqldump -u user --password=password $DB_NAME" > "$BACKUP_DIR/$FILENAME"; then
        log "Sauvegarde de $DB_NAME terminée."
    else
        log "Erreur lors de la sauvegarde de $DB_NAME"
    fi
}

# Vérification des conteneurs en cours d'exécution
log "Conteneurs Docker en cours d'exécution :"
$DOCKER_PATH ps >> "$LOG_FILE"

# Sauvegarder chaque base de données
backup_postgres "backupinfo" "safebase-database-1"
backup_postgres "backup" "safebase-backup-1"
backup_postgres "backuptwo" "safebase-backuptwo-1"
backup_mysql "fixtures_db" "safebase-fixtures_db-1"

log "Toutes les sauvegardes sont terminées."
