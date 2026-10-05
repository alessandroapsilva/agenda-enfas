<div class="card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <div>
            <strong class="d-block">Digitalização</strong>
            <span class="small text-secondary">Scanner local via Dynamsoft Dynamic Web TWAIN.</span>
        </div>
        <span class="badge text-bg-{{ config('dynamsoft.enabled') ? 'success':'light' }} {{ config('dynamsoft.enabled') ? '':'border' }}">
            {{ config('dynamsoft.enabled') ? 'Disponível':'Não configurado' }}
        </span>
    </div>
    <div class="card-body">
        @if(config('dynamsoft.enabled'))
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Resolução</label>
                    <select id="dwtResolution" class="form-select">
                        <option value="200">200 DPI</option>
                        <option value="300" selected>300 DPI</option>
                        <option value="400">400 DPI</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modo</label>
                    <select id="dwtPixelType" class="form-select">
                        <option value="rgb">Cor</option>
                        <option value="gray">Cinza</option>
                        <option value="bw">Preto e branco</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Frente e verso</label>
                    <select id="dwtDuplex" class="form-select">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="button" id="dwtScanButton" class="btn btn-primary">
                        <i class="bi bi-printer"></i>Digitalizar
                    </button>
                </div>
            </div>

            <div id="dwtStatus" class="small text-secondary mb-3">Aguardando scanner.</div>
            <div id="dwtcontrolContainer" class="ea-scanner-viewer"></div>

            <div class="row g-3 mt-1 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tipo do documento</label>
                    <select id="dwtCategory" class="form-select">
                        <option value="exam">Exame</option>
                        <option value="report">Laudo / relatório</option>
                        <option value="referral">Encaminhamento</option>
                        <option value="consent">Termo / consentimento</option>
                        <option value="authorization">Autorização</option>
                        <option value="identity">Documento pessoal</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Título</label>
                    <input id="dwtTitle" class="form-control" placeholder="Ex.: Exame laboratorial">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="button" id="dwtSaveButton" class="btn btn-light border" disabled>
                        <i class="bi bi-cloud-arrow-up"></i>Salvar no prontuário
                    </button>
                </div>
            </div>
        @else
            <div class="ea-scanner-empty">
                <i class="bi bi-printer"></i>
                <div>
                    <strong>Dynamsoft ainda não configurado</strong>
                    <span>Defina a licença e publique os recursos do Dynamic Web TWAIN para ativar a estação de digitalização.</span>
                </div>
            </div>
        @endif
    </div>
</div>

@if(config('dynamsoft.enabled'))
@push('js')
<script src="{{ rtrim(config('dynamsoft.resources_path'),'/') }}/dynamsoft.webtwain.initiate.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('dwtStatus');
    const scanButton = document.getElementById('dwtScanButton');
    const saveButton = document.getElementById('dwtSaveButton');
    let DWTObject = null;

    const setStatus = (message, error = false) => {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('text-danger', error);
        status.classList.toggle('text-secondary', !error);
    };

    if (!window.Dynamsoft?.DWT) {
        setStatus('Biblioteca do Dynamsoft não carregada.', true);
        return;
    }

    Dynamsoft.DWT.ResourcesPath = @json(rtrim(config('dynamsoft.resources_path'),'/'));
    Dynamsoft.DWT.ProductKey = @json(config('dynamsoft.product_key'));

    Dynamsoft.DWT.RegisterEvent('OnWebTwainReady', function () {
        DWTObject = Dynamsoft.DWT.GetWebTwain('dwtcontrolContainer');
        setStatus('Scanner pronto para uso.');
    });

    scanButton?.addEventListener('click', async () => {
        if (!DWTObject) {
            setStatus('Serviço local do Dynamsoft ainda não está pronto.', true);
            return;
        }

        try {
            await DWTObject.SelectSourceAsync();

            const pixelMap = {
                rgb: Dynamsoft.DWT.EnumDWT_PixelType.TWPT_RGB,
                gray: Dynamsoft.DWT.EnumDWT_PixelType.TWPT_GRAY,
                bw: Dynamsoft.DWT.EnumDWT_PixelType.TWPT_BW,
            };

            await DWTObject.AcquireImageAsync({
                IfShowUI: false,
                IfCloseSourceAfterAcquire: true,
                Resolution: Number(document.getElementById('dwtResolution')?.value || 300),
                PixelType: pixelMap[document.getElementById('dwtPixelType')?.value || 'rgb'],
                IfDuplexEnabled: document.getElementById('dwtDuplex')?.value === '1',
            });

            const pages = DWTObject.HowManyImagesInBuffer || 0;
            setStatus(pages ? pages + ' página(s) digitalizada(s).' : 'Nenhuma página recebida.', pages === 0);
            saveButton.disabled = pages === 0;
        } catch (error) {
            setStatus(error?.message || String(error), true);
        }
    });

    saveButton?.addEventListener('click', () => {
        if (!DWTObject || !DWTObject.HowManyImagesInBuffer) return;

        const indices = Array.from({length: DWTObject.HowManyImagesInBuffer}, (_, index) => index);

        saveButton.disabled = true;
        setStatus('Preparando PDF...');

        DWTObject.ConvertToBlob(
            indices,
            Dynamsoft.DWT.EnumDWT_ImageType.IT_PDF,
            async (blob) => {
                const form = new FormData();
                form.append('_token', @json(csrf_token()));
                form.append('patient_id', @json($appointment->patient_id));
                form.append('appointment_id', @json($appointment->id));
                @if($record)
                form.append('clinical_record_id', @json($record->id));
                @endif
                form.append('category', document.getElementById('dwtCategory')?.value || 'other');
                form.append('title', document.getElementById('dwtTitle')?.value || 'Documento digitalizado');
                form.append('source', 'scanner');
                form.append('scan_metadata', JSON.stringify({
                    provider: 'dynamsoft',
                    resolution: document.getElementById('dwtResolution')?.value,
                    pixel_type: document.getElementById('dwtPixelType')?.value,
                    duplex: document.getElementById('dwtDuplex')?.value === '1',
                    pages: indices.length,
                }));
                form.append('file', blob, 'digitalizacao-'+Date.now()+'.pdf');

                try {
                    const response = await fetch(@json(route('clinical-attachments.store')), {
                        method: 'POST',
                        body: form,
                        headers: {'Accept': 'application/json'},
                    });

                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        throw new Error(payload.message || 'Não foi possível salvar o documento.');
                    }

                    window.location.reload();
                } catch (error) {
                    saveButton.disabled = false;
                    setStatus(error?.message || String(error), true);
                }
            },
            (code, message) => {
                saveButton.disabled = false;
                setStatus(message || ('Falha ao gerar PDF: '+code), true);
            }
        );
    });
});
</script>
@endpush
@endif
