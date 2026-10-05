# ENFAS Scan Agent

Agente local do Agenda ENFAS para digitalização direta no navegador.

## Motor

O agente usa o NAPS2 SDK e expõe somente em `127.0.0.1:19876`.

Drivers previstos no Windows:

- TWAIN
- WIA
- eSCL

O Agenda consulta `/health`, lista dispositivos em `/devices` e solicita um PDF em `/scan`.
O navegador envia o PDF resultante para o storage privado do Agenda.

## Desenvolvimento

Requer .NET 8 SDK.

```powershell
cd tools\enfas-scan-agent
dotnet restore
dotnet run
```

Teste:

```powershell
Invoke-RestMethod http://127.0.0.1:19876/health
```

## Publicação Windows x64

```powershell
dotnet publish -c Release -r win-x64 --self-contained true -o publish
```

O processo deve rodar na sessão do usuário do Windows. Isso é intencional: drivers TWAIN/WIA podem depender da sessão interativa e não devem ser hospedados como serviço Session 0.

## Segurança

- bind somente em loopback;
- CORS limitado ao domínio do Agenda e desenvolvimento local;
- nenhum documento é persistido pelo agente após a resposta;
- o arquivo definitivo é armazenado pelo Laravel em storage privado;
- o Agenda calcula SHA-256 no upload.
