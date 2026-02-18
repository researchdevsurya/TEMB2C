@echo off
title TEM B2C Mail Worker
echo Starting Mail Queue Worker...
echo Press Ctrl+C to stop.

:loop
C:\xampp\php\php.exe c:\xampp\htdocs\temb2c\process_queue.php
echo Worker exited unexpectedly. Restarting in 5 seconds...
timeout /t 5
goto loop
