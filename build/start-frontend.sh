#!/bin/sh
cd /app
if [ ! -d "node_modules" ]; then
    npm install
fi
exec npm run dev -- --host 0.0.0.0
