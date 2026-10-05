document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-enfas-scan]');
    if (!root) return;

    const agentUrl = root.dataset.agentUrl;
    const uploadUrl = root.dataset.uploadUrl;
    const csrf = root.dataset.csrf;
    const status = root.querySelector('[data-scan-status]');
    const driver = root.querySelector('[data-scan-driver]');
    const device = root.querySelector('[data-scan-device]');
    const source = root.querySelector('[data-scan-source]');
    const dpi = root.querySelector('[data-scan-dpi]');
    const color = root.querySelector('[data-scan-color]');
    const button = root.querySelector('[data-scan-start]');
    const refresh = root.querySelector('[data-scan-refresh]');

    const setStatus = (message, type = 'muted') => {
        if (!status) return;
        status.textContent = message;
        status.className = 'small text-' + type;
    };

    async function loadDevices() {
        if (!agentUrl || !driver || !device) return;

        setStatus('Conectando ao ENFAS Scan Agent...');

        try {
            const health = await fetch(agentUrl + '/health', {
                signal: AbortSignal.timeout(4000),
            });
            if (!health.ok) throw new Error('Agente indisponível');

            const response = await fetch(
                agentUrl + '/devices?driver=' + encodeURIComponent(driver.value),
                {signal: AbortSignal.timeout(10000)}
            );
            if (!response.ok) throw new Error('Não foi possível listar scanners');

            const payload = await response.json();
            device.innerHTML = '';

            for (const item of (payload.devices || [])) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                device.appendChild(option);
            }

            setStatus(
                device.options.length
                    ? 'Agente conectado · ' + device.options.length + ' scanner(s)'
                    : 'Agente conectado · nenhum scanner encontrado',
                device.options.length ? 'success' : 'warning'
            );
        } catch (error) {
            device.innerHTML = '';
            setStatus('ENFAS Scan Agent não encontrado neste computador.', 'danger');
        }
    }

    async function scanAndUpload() {
        if (!device?.value) {
            setStatus('Selecione um scanner antes de digitalizar.', 'danger');
            return;
        }

        button.disabled = true;
        setStatus('Digitalizando...', 'primary');

        try {
            const scan = await fetch(agentUrl + '/scan', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    driver: driver.value,
                    device: device.value,
                    source: source.value,
                    dpi: Number(dpi.value),
                    color: color.value,
                }),
            });

            if (!scan.ok) {
                const detail = await scan.json().catch(() => ({}));
                throw new Error(detail.error || 'Falha na digitalização');
            }

            const blob = await scan.blob();
            const form = new FormData();
            form.append('_token', csrf);
            form.append('patient_id', root.dataset.patientId);
            form.append('appointment_id', root.dataset.appointmentId);
            if (root.dataset.recordId) form.append('clinical_record_id', root.dataset.recordId);
            form.append('category', root.querySelector('[data-scan-category]').value);
            form.append('title', root.querySelector('[data-scan-title]').value || 'Documento digitalizado');
            form.append('source', 'scanner');
            form.append('file', blob, 'digitalizacao.pdf');

            setStatus('Digitalização concluída. Salvando no prontuário...', 'primary');

            const upload = await fetch(uploadUrl, {
                method: 'POST',
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!upload.ok) {
                throw new Error('O scanner funcionou, mas o Agenda não conseguiu salvar o arquivo.');
            }

            setStatus('Documento digitalizado e salvo.', 'success');
            window.setTimeout(() => window.location.reload(), 600);
        } catch (error) {
            setStatus(error.message || 'Não foi possível digitalizar.', 'danger');
        } finally {
            button.disabled = false;
        }
    }

    root.addEventListener('shown.bs.modal', loadDevices);
    driver?.addEventListener('change', loadDevices);
    refresh?.addEventListener('click', loadDevices);
    button?.addEventListener('click', scanAndUpload);
});
