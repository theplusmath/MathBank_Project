#!/bin/bash
cd /var/www/html

NOW=$(date "+%Y-%m-%d %H:%M")
git add .
git commit -m "📦 자동 백업: $NOW"
git push origin main
	
