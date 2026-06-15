#!/bin/bash

##############################################################################
# NEXTHOSPITAL - Automated Database Backup Script
##############################################################################
# This script creates daily backups of the nexthospital database
# Usage: ./backup-database.sh
# Cron: 0 2 * * * /path/to/backup-database.sh
##############################################################################

# Configuration — set via environment or edit before running (never commit real passwords)
DB_NAME="${DB_NAME:-nexthospital}"
DB_USER="${DB_USER:-nexthospital}"
DB_PASS="${DB_PASSWORD:-${DB_PASS:-}}"
DB_HOST="${DB_HOST:-127.0.0.1}"
MYSQL_BIN="/Applications/XAMPP/xamppfiles/bin/mysql"
MYSQLDUMP_BIN="/Applications/XAMPP/xamppfiles/bin/mysqldump"

# Backup directory
BACKUP_DIR="/Applications/XAMPP/xamppfiles/htdocs/nexthospital/backend/storage/app/backups"
LOG_FILE="/Applications/XAMPP/xamppfiles/htdocs/nexthospital/backend/storage/logs/backup.log"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Timestamp for backup file
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_backup_${TIMESTAMP}.sql"
COMPRESSED_FILE="${BACKUP_FILE}.gz"

# Log function
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_message "========================================="
log_message "Starting database backup process"
log_message "========================================="

if [ -z "$DB_PASS" ]; then
    log_message "ERROR: Set DB_PASSWORD or DB_PASS environment variable before running this script"
    exit 1
fi

# Check if mysqldump exists
if [ ! -f "$MYSQLDUMP_BIN" ]; then
    log_message "ERROR: mysqldump not found at $MYSQLDUMP_BIN"
    exit 1
fi

# Perform backup
log_message "Backing up database: $DB_NAME"
"$MYSQLDUMP_BIN" -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_FILE" 2>&1

# Check if backup was successful
if [ $? -eq 0 ]; then
    log_message "Database backup created successfully: $BACKUP_FILE"
    
    # Compress backup
    log_message "Compressing backup file..."
    gzip "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        log_message "Backup compressed successfully: $COMPRESSED_FILE"
        
        # Get backup file size
        BACKUP_SIZE=$(du -h "$COMPRESSED_FILE" | cut -f1)
        log_message "Backup size: $BACKUP_SIZE"
    else
        log_message "ERROR: Failed to compress backup file"
        exit 1
    fi
else
    log_message "ERROR: Database backup failed"
    exit 1
fi

# Cleanup old backups (keep last 30 days)
log_message "Cleaning up old backups (keeping last 30 days)..."
find "$BACKUP_DIR" -name "${DB_NAME}_backup_*.sql.gz" -type f -mtime +30 -delete
REMAINING_BACKUPS=$(find "$BACKUP_DIR" -name "${DB_NAME}_backup_*.sql.gz" -type f | wc -l)
log_message "Remaining backups: $REMAINING_BACKUPS"

# Calculate total backup size
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log_message "Total backup directory size: $TOTAL_SIZE"

log_message "========================================="
log_message "Backup process completed successfully"
log_message "========================================="

# Optional: Send notification (uncomment if needed)
# You can add email notification here using mail command or API
# echo "Database backup completed successfully" | mail -s "Backup Success" admin@yourhospital.com

exit 0
