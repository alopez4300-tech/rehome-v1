#!/bin/bash

echo "🔧 Quick Fix for Codespace Setup Issues"
echo "======================================"

# Check what OS we're running
if command -v apt-get >/dev/null 2>&1; then
    echo "✅ Ubuntu/Debian detected - should work normally"
    echo "Run: bash .devcontainer/post-create.sh"
elif command -v apk >/dev/null 2>&1; then
    echo "⚠️  Alpine Linux detected - running Alpine setup"
    bash .devcontainer/alpine-setup.sh
else
    echo "❌ Unknown OS - manual setup required"
fi

echo ""
echo "🛠️  Manual Setup Commands (if automated setup fails):"
echo ""
echo "1. Create Laravel Backend:"
echo "   bash .devcontainer/create-backend-structure.sh"
echo ""
echo "2. Create React Frontend:"
echo "   bash .devcontainer/create-frontend-structure.sh"
echo ""
echo "3. Start Development Servers:"
echo "   make be  # Laravel backend on :8000"
echo "   make fe  # React frontend on :3000"
echo ""
echo "4. Access Admin Panel:"
echo "   URL: http://localhost:8000/admin"
echo "   Login: admin1@rehome.build / password"
echo ""