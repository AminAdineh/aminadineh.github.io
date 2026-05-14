#!/bin/bash
# Setup script for cPanel cron job
# This script helps you set up the cron job correctly

echo "=========================================="
echo "Blog Automation - Cron Job Setup Helper"
echo "=========================================="
echo ""

# Get current directory
CURRENT_DIR=$(pwd)
echo "Current directory: $CURRENT_DIR"
echo ""

# Find Python
echo "Looking for Python 3..."
PYTHON_CMD=""
for path in /usr/local/bin/python3 /usr/bin/python3 python3; do
    if command -v $path &> /dev/null; then
        PYTHON_CMD=$path
        echo "✓ Found Python: $PYTHON_CMD"
        $PYTHON_CMD --version
        break
    fi
done

if [ -z "$PYTHON_CMD" ]; then
    echo "✗ Python 3 not found!"
    echo "Please install Python 3 or use the PHP wrapper instead."
    exit 1
fi

echo ""
echo "Checking Python dependencies..."
$PYTHON_CMD -c "import requests, bs4" 2>&1
if [ $? -ne 0 ]; then
    echo "✗ Required packages not installed"
    echo "Installing dependencies..."
    $PYTHON_CMD -m pip install --user requests beautifulsoup4 lxml Pillow
else
    echo "✓ Dependencies are installed"
fi

echo ""
echo "=========================================="
echo "Cron Job Command:"
echo "=========================================="
echo ""
echo "Copy this command to your cPanel Cron Jobs:"
echo ""
echo "cd $CURRENT_DIR && $PYTHON_CMD blog_automation.py >> blog_automation.log 2>&1"
echo ""
echo "Or if using PHP wrapper:"
echo ""
echo "php $CURRENT_DIR/run_blog_automation.php >> blog_automation.log 2>&1"
echo ""
echo "Recommended schedule: Daily at 6:00 AM"
echo "Cron format: 0 6 * * *"
echo ""
echo "=========================================="

