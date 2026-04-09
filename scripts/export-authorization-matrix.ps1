param(
    [string]$OutputDir = ".ai/decisions/generated",
    [string]$MatrixFile = ".ai/decisions/authorization-matrix.json"
)

$ErrorActionPreference = "Stop"
$ScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Resolve-Path (Join-Path $ScriptRoot "..")
$ResolvedMatrixFile = Join-Path $RepoRoot $MatrixFile
$ResolvedOutputDir = Join-Path $RepoRoot $OutputDir

if (!(Test-Path $ResolvedMatrixFile)) {
    throw "Matrix file not found: $ResolvedMatrixFile"
}

if (!(Test-Path $ResolvedOutputDir)) {
    New-Item -ItemType Directory -Path $ResolvedOutputDir | Out-Null
}

$matrix = Get-Content $ResolvedMatrixFile -Raw | ConvertFrom-Json
$routes = php artisan route:list --json | ConvertFrom-Json

$appRoutes = $routes | Where-Object {
    $_.action -and
    $_.action -ne "Closure" -and
    $_.action -notlike "Barryvdh\*" -and
    $_.action -notlike "Spatie\*" -and
    $_.uri -notlike "_debugbar/*" -and
    $_.uri -notlike "_ignition/*" -and
    $_.uri -ne "up"
}

$gateRows = $matrix.gates | ForEach-Object {
    [pscustomobject]@{
        name  = $_.name
        kind  = $_.kind
        level = $_.level
        roles = $_.roles
        notes = $_.notes
    }
}

$resourceRows = $matrix.resources | ForEach-Object {
    [pscustomobject]@{
        resource      = $_.resource
        read_roles    = $_.read_roles
        create_roles  = $_.create_roles
        update_roles  = $_.update_roles
        delete_roles  = $_.delete_roles
        read_level    = $_.read_level
        write_level   = $_.write_level
        notes         = $_.notes
    }
}

$actionMap = @{}
foreach ($item in $matrix.actionProtections) {
    $actionMap[$item.action] = $item
}

$gateMap = @{}
foreach ($gate in $matrix.gates) {
    $gateMap[$gate.name] = $gate
}

$resourceMap = @{}
foreach ($resource in $matrix.resources) {
    $resourceMap[$resource.resource] = $resource
}

function Get-DerivedAuthorizationFromMiddleware {
    param(
        [object[]]$MiddlewareList
    )

    $authorizeMiddlewares = @($MiddlewareList | Where-Object { $_ -like 'Illuminate\Auth\Middleware\Authorize:*' })
    if ($authorizeMiddlewares.Count -eq 0) {
        return $null
    }

    $policyAuthorizers = @($authorizeMiddlewares | Where-Object { $_ -notlike 'Illuminate\Auth\Middleware\Authorize:access*' })
    if ($policyAuthorizers.Count -eq 0) {
        return $null
    }

    return [pscustomobject]@{
        auth_type  = "route_middleware_authorize"
        auth_name  = ($policyAuthorizers -replace '^Illuminate\\Auth\\Middleware\\Authorize:', '') -join " | "
        auth_level = "middleware_authorize"
        roles      = ""
        notes      = "Derived from route middleware Authorize:*"
    }
}

function Get-ControllerResource {
    param(
        [string]$ControllerClass
    )

    switch -Regex ($ControllerClass) {
        '(^|\\)CargoController$' { return 'Cargo' }
        '(^|\\)CatalogItemController$' { return 'CatalogItem' }
        '(^|\\)CustomerController$' { return 'Customer' }
        '(^|\\)DriverJourneyController$' { return 'Journey' }
        '(^|\\)DriverOrderController$' { return 'Order' }
        '(^|\\)HolderController$' { return 'Holder' }
        '(^|\\)JourneyController$' { return 'Journey' }
        '(^|\\)OrderController$' { return 'Order' }
        '(^|\\)RecipeController$' { return 'Recipe' }
        '(^|\\)SiteController$' { return 'Site' }
        '(^|\\)TrailerController$' { return 'Trailer' }
        '(^|\\)UserController$' { return 'User' }
        '(^|\\)VehicleController$' { return 'Vehicle' }
        '(^|\\)WithdrawController$' { return 'Withdraw' }
        default { return $null }
    }
}

function Get-AbilityFromMethod {
    param(
        [string]$MethodName
    )

    switch -Regex ($MethodName) {
        '^(index|search|list)$' { return 'viewAny' }
        '^(show|edit)$' { return 'view' }
        '^(create|store)$' { return 'create' }
        '^(update|updateState|sync|recalculateRisk)$' { return 'update' }
        '^(destroy|delete|restore|forceDelete)$' { return 'delete' }
        default { return $null }
    }
}

function Get-RolesForResourceAbility {
    param(
        [string]$ResourceName,
        [string]$AbilityName
    )

    if (-not $resourceMap.ContainsKey($ResourceName)) {
        return $null
    }

    $resource = $resourceMap[$ResourceName]
    switch ($AbilityName) {
        'viewAny' {
            return [pscustomobject]@{
                level = $resource.read_level
                roles = $resource.read_roles
            }
        }
        'view' {
            return [pscustomobject]@{
                level = $resource.read_level
                roles = $resource.read_roles
            }
        }
        'create' {
            return [pscustomobject]@{
                level = $resource.write_level
                roles = $resource.create_roles
            }
        }
        'update' {
            return [pscustomobject]@{
                level = $resource.write_level
                roles = $resource.update_roles
            }
        }
        'delete' {
            return [pscustomobject]@{
                level = $resource.write_level
                roles = $resource.delete_roles
            }
        }
        default { return $null }
    }
}

function Get-InferredPolicyFromAction {
    param(
        [string]$Action
    )

    if (-not $Action -or $Action -notmatch '@') {
        return $null
    }

    $parts = $Action -split '@', 2
    $controllerClass = $parts[0]
    $methodName = $parts[1]

    $resourceName = Get-ControllerResource -ControllerClass $controllerClass
    $abilityName = Get-AbilityFromMethod -MethodName $methodName

    if (-not $resourceName -or -not $abilityName) {
        return $null
    }

    $roleData = Get-RolesForResourceAbility -ResourceName $resourceName -AbilityName $abilityName
    if (-not $roleData) {
        return $null
    }

    return [pscustomobject]@{
        auth_type  = 'policy_inferred'
        auth_name  = "${resourceName}Policy.${abilityName}"
        auth_level = $roleData.level
        roles      = $roleData.roles
        notes      = "Inferred from controller action ${controllerClass}@${methodName}"
        resource   = $resourceName
        ability    = $abilityName
    }
}

function Get-AreaGateAuthorization {
    param(
        [object[]]$MiddlewareList
    )

    $areaGateMiddleware = @($MiddlewareList | Where-Object { $_ -like 'Illuminate\Auth\Middleware\Authorize:access*' } | Select-Object -First 1)
    if ($areaGateMiddleware.Count -eq 0) {
        return $null
    }

    $gateName = $areaGateMiddleware[0] -replace '^Illuminate\\Auth\\Middleware\\Authorize:', ''
    if (-not $gateMap.ContainsKey($gateName)) {
        return [pscustomobject]@{
            auth_type  = 'gate'
            auth_name  = $gateName
            auth_level = 'gate'
            roles      = ''
            notes      = 'Derived from route area gate'
        }
    }

    $gate = $gateMap[$gateName]
    return [pscustomobject]@{
        auth_type  = $gate.kind
        auth_name  = $gate.name
        auth_level = $gate.level
        roles      = $gate.roles
        notes      = $gate.notes
    }
}

$routeRows = $appRoutes | ForEach-Object {
    $match = $null
    if ($actionMap.ContainsKey($_.action)) {
        $match = $actionMap[$_.action]
    } else {
        $match = Get-DerivedAuthorizationFromMiddleware -MiddlewareList $_.middleware
        if (-not $match) {
            $match = Get-InferredPolicyFromAction -Action $_.action
        }
        if (-not $match) {
            $match = Get-AreaGateAuthorization -MiddlewareList $_.middleware
        }
    }

    [pscustomobject]@{
        method              = $_.method
        uri                 = $_.uri
        name                = $_.name
        action              = $_.action
        middleware          = ($_.middleware -join " | ")
        has_auth            = [bool]($_.middleware -contains 'Illuminate\Auth\Middleware\Authenticate')
        has_verified        = [bool]($_.middleware -contains 'Illuminate\Auth\Middleware\EnsureEmailIsVerified')
        area_gate           = (@($_.middleware | Where-Object { $_ -like 'Illuminate\Auth\Middleware\Authorize:access*' }) -join " | ")
        resource            = if ($match -and $match.PSObject.Properties.Name -contains 'resource') { $match.resource } else { "" }
        ability             = if ($match -and $match.PSObject.Properties.Name -contains 'ability') { $match.ability } else { "" }
        authorization_type  = if ($match) { $match.auth_type } else { "" }
        authorization_name  = if ($match) { $match.auth_name } else { "" }
        authorization_level = if ($match) { $match.auth_level } else { "" }
        roles               = if ($match) { $match.roles } else { "" }
        notes               = if ($match) { $match.notes } else { "" }
    }
}

$reportLines = @()
$reportLines += "# Authorization Matrix"
$reportLines += ""
$reportLines += "Data: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
$reportLines += "Source: $MatrixFile + php artisan route:list --json"
$reportLines += ""
$reportLines += "## Gates"
$reportLines += ""
$reportLines += "| Name | Kind | Level | Roles | Notes |"
$reportLines += "| --- | --- | --- | --- | --- |"
foreach ($row in $gateRows) {
    $reportLines += "| $($row.name) | $($row.kind) | $($row.level) | $($row.roles) | $($row.notes) |"
}
$reportLines += ""
$reportLines += "## Resources"
$reportLines += ""
$reportLines += "| Resource | Read Roles | Create Roles | Update Roles | Delete Roles | Read Level | Write Level | Notes |"
$reportLines += "| --- | --- | --- | --- | --- | --- | --- | --- |"
foreach ($row in $resourceRows) {
    $reportLines += "| $($row.resource) | $($row.read_roles) | $($row.create_roles) | $($row.update_roles) | $($row.delete_roles) | $($row.read_level) | $($row.write_level) | $($row.notes) |"
}
$reportLines += ""
$reportLines += "## Routes"
$reportLines += ""
$reportLines += "| Method | URI | Name | Action | Area Gate | Resource | Ability | Authorization | Level | Roles |"
$reportLines += "| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |"
foreach ($row in $routeRows | Sort-Object uri, method) {
    $auth = if ($row.authorization_name) { $row.authorization_name } else { "" }
    $reportLines += "| $($row.method) | $($row.uri) | $($row.name) | $($row.action) | $($row.area_gate) | $($row.resource) | $($row.ability) | $auth | $($row.authorization_level) | $($row.roles) |"
}

$gateRows | Export-Csv -Path (Join-Path $ResolvedOutputDir "authorization-gates.csv") -NoTypeInformation -Encoding UTF8
$resourceRows | Export-Csv -Path (Join-Path $ResolvedOutputDir "authorization-resources.csv") -NoTypeInformation -Encoding UTF8
$routeRows | Sort-Object uri, method | Export-Csv -Path (Join-Path $ResolvedOutputDir "authorization-routes.csv") -NoTypeInformation -Encoding UTF8
$reportLines | Set-Content -Path (Join-Path $ResolvedOutputDir "authorization-matrix.md") -Encoding UTF8

Write-Output "Generated authorization artifacts in $ResolvedOutputDir"
