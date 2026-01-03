#!/usr/bin/env bash

# Run Vite in background
yarn dev &

# Run PHP server
php -S localhost:8000 -t Infrastructure/Http/Public
