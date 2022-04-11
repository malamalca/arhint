@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vendor/phpunit/phpunit/phpunit
D:\xampp\php\php.exe "%BIN_TARGET%" %*
