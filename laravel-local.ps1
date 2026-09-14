# Executa o Artisan com as extensoes PHP preparadas para este computador.
$ErrorActionPreference = 'Stop'
$previousPhpConfig = $env:PHPRC
$env:PHPRC = "$PSScriptRoot\php-local.ini"
Push-Location $PSScriptRoot
try {
    & php -c "$PSScriptRoot\php-local.ini" artisan @args
    $commandExitCode = $LASTEXITCODE
} finally {
    Pop-Location
    $env:PHPRC = $previousPhpConfig
}
exit $commandExitCode
