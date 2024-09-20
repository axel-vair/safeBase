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

# Récupérer la fréquence depuis la base de données
FREQUENCY=$(php /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/bin/console app:get-cron-frequency)
# Vérifier si la fréquence est définie
if [ -z "$FREQUENCY" ]; then
    log "Erreur : Aucune fréquence trouvée."
    exit 1
fi

# Exécuter la sauvegarde en fonction de la fréquence
if [ "$FREQUENCY" == "daily" ]; then
    # Effectuer une sauvegarde quotidienne
    backup_postgres "backupinfo" "safebase-database-1"
    backup_postgres "backup" "safebase-backup-1"
    backup_postgres "backuptwo" "safebase-backuptwo-1"
    backup_mysql "fixtures_db" "safebase-fixtures_db-1"
elif [ "$FREQUENCY" == "weekly" ]; then
    if [ "$(date +%u)" -eq 1 ]; then # Lundi
        backup_postgres "backupinfo" "safebase-database-1"
        backup_postgres "backup" "safebase-backup-1"
        backup_postgres "backuptwo" "safebase-backuptwo-1"
        backup_mysql "fixtures_db" "safebase-fixtures_db-1"
    fi
elif [ "$FREQUENCY" == "monthly" ]; then
    if [ "$(date +%d)" -eq 1 ]; then # Premier jour du mois
        backup_postgres "backupinfo" "safebase-database-1"
        backup_postgres "backup" "safebase-backup-1"
        backup_postgres "backuptwo" "safebase-backuptwo-1"
        backup_mysql "fixtures_db" "safebase-fixtures_db-1"
    fi
else
    log "Fréquence non reconnue : $FREQUENCY"
fi

log "Toutes les sauvegardes sont terminées."
