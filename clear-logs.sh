#!/bin/bash
# Clear Nimbbl SDK Log Files
# Usage: ./clear-logs.sh

LOG_DIR="logs"
LOG_FILE="$LOG_DIR/nimbbl_debug.log"

echo "🧹 Clearing Nimbbl SDK log files..."

if [ -f "$LOG_FILE" ]; then
    # Get file size before clearing
    SIZE=$(ls -lh "$LOG_FILE" | awk '{print $5}')
    echo "  Current log file size: $SIZE"
    
    # Clear the log file
    > "$LOG_FILE"
    echo "  [OK] Log file cleared: $LOG_FILE"
else
    echo "  [INFO]  Log file doesn't exist yet: $LOG_FILE"
fi

# Check for other log files
OTHER_LOGS=$(find "$LOG_DIR" -name "*.log" -type f 2>/dev/null | wc -l | tr -d ' ')
if [ "$OTHER_LOGS" -gt 0 ]; then
    echo "  Found $OTHER_LOGS other log file(s)"
    read -p "  Clear all log files? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -f "$LOG_DIR"/*.log
        echo "  [OK] All log files cleared"
    fi
fi

echo " Done!"

