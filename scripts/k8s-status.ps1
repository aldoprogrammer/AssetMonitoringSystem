$ErrorActionPreference = "Stop"

Write-Host "Kubernetes jobs" -ForegroundColor Green
kubectl get jobs -n asset-monitoring-system

Write-Host ""
Write-Host "Kubernetes pods" -ForegroundColor Green
kubectl get pods -n asset-monitoring-system

Write-Host ""
Write-Host "Kubernetes services" -ForegroundColor Green
kubectl get svc -n asset-monitoring-system

Write-Host ""
Write-Host "Recent identity bootstrap logs" -ForegroundColor Green
kubectl logs job/identity-bootstrap -n asset-monitoring-system --tail=40
