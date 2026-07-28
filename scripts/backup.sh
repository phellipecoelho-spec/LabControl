#!/bin/bash
# LabControl PostgreSQL Backup Script
# Usage: ./scripts/backup.sh
# Runs pg_dump, compresses with gzip, and rotates old backups (30 days default)

set -euo pipefail

# Configuration (can be overridden via environment variables)
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="${BACKUP_DIR:-/backups}"
DB_NAME="${DB_NAME:-labcontrol}"
DB_USER="${DB_USER:-labcontrol}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.dump"

echo "=== LabControl PostgreSQL Backup ==="
echo "Timestamp: $(date)"
echo "Database: $DB_NAME"
echo "Host: $DB_HOST:$DB_PORT"
echo "User: $DB_USER"
echo "Backup directory: $BACKUP_DIR"
echo "Retention: $RETENTION_DAYS days"
echo ""

# Run pg_dump in custom format (compressed, allows parallel restore)
echo "Creating backup: $BACKUP_FILE"
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -F c -f "$BACKUP_FILE"

# Compress with gzip
echo "Compressing backup..."
gzip "$BACKUP_FILE"

COMPRESSED_FILE="${BACKUP_FILE}.gz"
FILE_SIZE=$(du -h "$COMPRESSED_FILE" | cut -f1)

echo ""
echo "Backup completed successfully!"
echo "File: $COMPRESSED_FILE"
echo "Size: $FILE_SIZE"

# Rotate old backups
echo ""
echo "Rotating backups older than $RETENTION_DAYS days..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "${DB_NAME}_*.dump.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)

if [ "$DELETED_COUNT" -gt 0 ]; then
    echo "Deleted $DELETED_COUNT old backup(s)"
else
    echo "No old backups to delete"
fi

echo ""
echo "=== Backup complete ==="