# --- CONFIGURAÇÕES ---
$APP_PASSWORD = "09038721" # Sua senha
$ANTIGRAVITY_PATH = "$env:LOCALAPPDATA\Programs\Antigravity\Antigravity.exe"

# Limpa a tela para ficar organizado
Clear-Host
Write-Host "🧹 Limpando processos antigos..." -ForegroundColor Cyan
# Corrigido: Lista separada por vírgula
Stop-Process -Name "Antigravity", "ngrok" -ErrorAction SilentlyContinue

# 1. Inicia o Antigravity (Suprimindo logs de erro do Electron para o terminal não ficar sujo)
Write-Host "🚀 Iniciando Antigravity..." -ForegroundColor Green
Start-Process -FilePath $ANTIGRAVITY_PATH -ArgumentList ". --remote-debugging-port=7800" -WindowStyle Normal

# 2. Inicia o ngrok
Write-Host "🌐 Abrindo túnel ngrok..." -ForegroundColor Yellow
# Removido o basic-auth para simplificar, já que o Omni tem o Passcode
Start-Process -FilePath "ngrok" -ArgumentList "http https://localhost:4747"

# 3. Inicia o Omni
Write-Host "📱 Iniciando Omni Remote Chat..." -ForegroundColor Magenta
$env:APP_PASSWORD = $APP_PASSWORD

# Garante que o npx foque na porta 7800
npx omni-antigravity-remote-chat