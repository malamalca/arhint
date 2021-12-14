@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vendor/phpunit/phpunit/phpunit
D:\PHP8\php.exe "%BIN_TARGET%" %*
