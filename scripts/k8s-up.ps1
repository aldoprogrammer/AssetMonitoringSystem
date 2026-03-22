param(
    [switch]$BuildImages
)

$ErrorActionPreference = "Stop"

Write-Host "Checking Docker Desktop Kubernetes..." -ForegroundColor Cyan

try {
    kubectl cluster-info | Out-Null
} catch {
    Write-Host "Docker Desktop Kubernetes is not reachable yet." -ForegroundColor Yellow
    Write-Host "Open Docker Desktop > Settings > Kubernetes, enable it, then wait until it is running." -ForegroundColor Yellow
    exit 1
}

if ($BuildImages) {
    Write-Host "Building local images with Docker Compose..." -ForegroundColor Cyan
    docker compose build
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

Write-Host "Applying Kubernetes manifests..." -ForegroundColor Cyan
kubectl apply -k kubernetes/base
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "Current jobs:" -ForegroundColor Green
kubectl get jobs -n asset-monitoring-system

Write-Host ""
Write-Host "Current pods:" -ForegroundColor Green
kubectl get pods -n asset-monitoring-system

Write-Host ""
Write-Host "Current services:" -ForegroundColor Green
kubectl get svc -n asset-monitoring-system

Write-Host ""
Write-Host "If you are not using ingress yet, port-forward these:" -ForegroundColor Cyan
Write-Host "kubectl port-forward svc/gateway 8080:80 -n asset-monitoring-system"
Write-Host "kubectl port-forward svc/swagger-ui 8081:8080 -n asset-monitoring-system"
