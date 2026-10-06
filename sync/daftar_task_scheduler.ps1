# WANDAI — Daftarkan Auto-Sync Push ke Windows Task Scheduler
# Jalankan PowerShell sebagai Administrator:
#   powershell -ExecutionPolicy Bypass -File "C:\wandai\sync\daftar_task_scheduler.ps1"
#
# Cara kerja:
#   PC lokal (FortiClient aktif) scrape community.bps.go.id → push ke server WANDAI
#   Task berjalan setiap hari kerja pukul 07:30.
#
# PRASYARAT:
#   1. FortiClient VPN BPS harus sudah konek sebelum jam 07:30
#   2. PHP harus terinstall di PC (sesuaikan $phpExe di bawah)

$phpExe   = "C:\xampp\php\php.exe"       # Sesuaikan dengan lokasi PHP di PC kamu
$script   = "C:\wandai\sync\push_to_server.php"
$taskName = "WANDAI_Push_Sync_Pegawai"
$logFile  = "C:\wandai\sync\sync.log"
$startTime = "07:30"

# Hapus task lama jika ada
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue

$action = New-ScheduledTaskAction `
    -Execute $phpExe `
    -Argument "`"$script`" >> `"$logFile`" 2>&1"

# Setiap hari kerja Senin-Jumat pukul 07:30
$trigger = New-ScheduledTaskTrigger `
    -Weekly `
    -DaysOfWeek Monday,Tuesday,Wednesday,Thursday,Friday `
    -At $startTime

$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

Register-ScheduledTask `
    -TaskName $taskName `
    -Action   $action `
    -Trigger  $trigger `
    -Settings $settings `
    -RunLevel Highest `
    -Force

Write-Host ""
Write-Host "Task '$taskName' berhasil didaftarkan." -ForegroundColor Green
Write-Host "Jadwal  : Setiap Senin-Jumat pukul $startTime"
Write-Host "Log     : $logFile"
Write-Host "Endpoint: https://www.wandaipaniai.web.bps.go.id/api/receive_sync.php"
Write-Host ""
Write-Host "Untuk uji coba sekarang:" -ForegroundColor Yellow
Write-Host "  Start-ScheduledTask -TaskName '$taskName'"
Write-Host ""
Write-Host "Atau jalankan langsung:" -ForegroundColor Yellow
Write-Host "  $phpExe `"$script`""
