(() => {
    const video = document.getElementById('camera-video');
    const cameraPanel = document.getElementById('camera-panel');
    const canvas = document.getElementById('camera-canvas');
    const startButton = document.getElementById('camera-start');
    const captureButton = document.getElementById('camera-capture');
    const waiting = document.getElementById('camera-waiting');
    const status = document.getElementById('camera-status');
    const result = document.getElementById('camera-result');
    const plate = document.getElementById('camera-plate');
    const resultMessage = document.getElementById('camera-result-message');
    const authorization = document.getElementById('camera-authorization');
    const actions = document.getElementById('camera-actions');
    const releaseButton = document.getElementById('camera-release');
    const triageButton = document.getElementById('camera-triage');
    const accessType = document.getElementById('camera-access-type');
    const triageForm = document.getElementById('camera-triage-form');
    const triageFeedback = document.getElementById('camera-triage-feedback');
    const triageSubmit = document.getElementById('camera-triage-submit');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!video || !canvas || !startButton || !captureButton) {
        return;
    }

    let stream = null;
    let recognizedPlate = null;
    let recognizedVehicle = null;
    let recognizedMessage = '';
    let triageUrl = null;

    const setStatus = (text, type = 'neutral') => {
        status.textContent = text;
        status.className = `pill ${type}`;
    };

    const requestJson = async (url, body, method = 'POST') => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = Object.values(data.errors || {}).flat()[0];
            throw new Error(validationMessage || data.message || 'Não foi possível concluir a operação.');
        }

        return data;
    };

    const stopCamera = () => {
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        video.srcObject = null;
        captureButton.disabled = true;
    };

    const startCamera = async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            waiting.querySelector('strong').textContent = 'Câmera não disponível';
            waiting.querySelector('p').textContent = 'Use HTTPS ou acesse o sistema por localhost.';
            setStatus('Indisponível', 'danger');
            return;
        }

        stopCamera();
        setStatus('Solicitando acesso...', 'neutral');
        startButton.disabled = true;

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: { ideal: 'environment' },
                },
                audio: false,
            });

            video.srcObject = stream;
            await video.play();
            waiting.hidden = true;
            captureButton.disabled = false;
            startButton.textContent = 'Reiniciar câmera';
            setStatus('Câmera ativa', 'success');
        } catch (error) {
            waiting.hidden = false;
            waiting.querySelector('strong').textContent = 'Acesso à câmera bloqueado';
            waiting.querySelector('p').textContent = 'Autorize a câmera nas permissões do navegador e tente novamente.';
            setStatus('Sem acesso', 'danger');
        } finally {
            startButton.disabled = false;
        }
    };

    const showError = (message) => {
        recognizedPlate = null;
        recognizedVehicle = null;
        triageUrl = null;
        result.hidden = false;
        plate.textContent = 'Não reconhecida';
        resultMessage.textContent = message;
        authorization.textContent = 'Tente novamente';
        authorization.className = 'pill danger';
        if (actions) actions.hidden = true;
        if (triageForm) triageForm.hidden = true;
    };

    const updateAccessActions = () => {
        if (!actions || !recognizedVehicle) return;

        const saida = accessType?.value === 'saida';
        releaseButton.hidden = saida || !recognizedVehicle.cadastrado;
        triageButton.hidden = !saida && recognizedVehicle.cadastrado;
        triageButton.textContent = saida ? 'Registrar saída' : 'Realizar triagem';

        authorization.textContent = saida
            ? 'Saída livre'
            : (recognizedVehicle.autorizado ? 'Autorizado' : (recognizedVehicle.cadastrado ? 'Não autorizado' : 'Não cadastrado'));
        authorization.className = `pill ${saida || recognizedVehicle.autorizado ? 'success' : 'danger'}`;
        resultMessage.textContent = saida ? 'Saída livre. Nenhuma autorização é necessária.' : recognizedMessage;
    };

    const registerManualAccess = async (release) => {
        if (!recognizedPlate || !cameraPanel?.dataset.manualAccessUrl) return;

        const buttons = [releaseButton, triageButton].filter(Boolean);
        buttons.forEach((button) => { button.disabled = true; });
        if (releaseButton) releaseButton.textContent = 'Registrando...';
        if (triageButton) triageButton.textContent = 'Iniciando triagem...';

        try {
            const data = await requestJson(cameraPanel.dataset.manualAccessUrl, {
                placa: recognizedPlate,
                tipo: accessType?.value || 'entrada',
                liberar_manualmente: release,
            });

            if (release) {
                actions.hidden = true;
                resultMessage.textContent = data.message || 'Acesso liberado e registrado.';
                authorization.textContent = 'Acesso liberado';
                authorization.className = 'pill success';
                setStatus('Acesso registrado', 'success');
                return;
            }

            if (accessType?.value === 'saida') {
                actions.hidden = true;
                resultMessage.textContent = 'Saída registrada livremente.';
                authorization.textContent = 'Saída registrada';
                authorization.className = 'pill success';
                setStatus('Saída registrada', 'success');
                return;
            }

            if (!data.triagem_id) {
                throw new Error('Não foi possível iniciar a triagem deste acesso.');
            }

            triageUrl = cameraPanel.dataset.triageUrlTemplate
                .replace('__TRIAGEM_ID__', encodeURIComponent(data.triagem_id));
            triageForm.reset();
            triageForm.hidden = false;
            actions.hidden = true;
            triageFeedback.textContent = data.message || 'Informe os dados do visitante para concluir a triagem.';
            resultMessage.textContent = 'Acesso pendente. Preencha os dados da triagem.';
            authorization.textContent = 'Triagem pendente';
            authorization.className = 'pill neutral';
            triageForm.querySelector('[name="nome_visitante"]').focus();
            setStatus('Triagem pendente', 'neutral');
        } catch (error) {
            resultMessage.textContent = error.message || 'Não foi possível registrar o acesso.';
            authorization.textContent = 'Operação não concluída';
            authorization.className = 'pill danger';
            setStatus('Falha na operação', 'danger');
        } finally {
            if (releaseButton) {
                releaseButton.disabled = false;
                releaseButton.textContent = 'Liberar acesso';
            }
            if (triageButton) {
                triageButton.disabled = false;
                triageButton.textContent = accessType?.value === 'saida' ? 'Registrar saída' : 'Realizar triagem';
            }
        }
    };

    captureButton.addEventListener('click', async () => {
        if (!stream || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            showError('A câmera ainda não está pronta para capturar.');
            return;
        }

        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const image = canvas.toDataURL('image/jpeg', 0.9);

        captureButton.disabled = true;
        captureButton.textContent = 'Reconhecendo...';
        setStatus('Processando imagem...', 'neutral');
        result.hidden = true;

        try {
            const data = await requestJson(
                cameraPanel?.dataset.recognitionUrl || '/camera/reconhecer',
                { image },
            );

            result.hidden = false;
            recognizedPlate = data.placa;
            recognizedVehicle = data;
            plate.textContent = data.placa_formatada || data.placa;
            recognizedMessage = data.condutor
                ? `${data.message} Condutor: ${data.condutor}.`
                : data.message;
            if (actions) {
                updateAccessActions();
                actions.hidden = false;
            }
            if (triageForm) triageForm.hidden = true;
            setStatus('Placa reconhecida', 'success');
        } catch (error) {
            showError(error.message || 'O reconhecimento não pôde ser concluído.');
            setStatus('Falha no reconhecimento', 'danger');
        } finally {
            captureButton.disabled = !stream;
            captureButton.textContent = 'Tirar foto e reconhecer';
        }
    });

    releaseButton?.addEventListener('click', () => registerManualAccess(true));
    triageButton?.addEventListener('click', () => registerManualAccess(false));
    accessType?.addEventListener('change', updateAccessActions);

    triageForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!triageUrl) return;

        triageSubmit.disabled = true;
        triageFeedback.textContent = 'Salvando triagem...';

        const formData = Object.fromEntries(new FormData(triageForm).entries());

        try {
            await requestJson(triageUrl, formData, 'PUT');
            const autorizada = formData.decisao === 'autorizada';
            resultMessage.textContent = autorizada
                ? 'Triagem concluída. Acesso autorizado.'
                : 'Triagem concluída. Acesso negado.';
            authorization.textContent = autorizada ? 'Autorizado' : 'Negado';
            authorization.className = `pill ${autorizada ? 'success' : 'danger'}`;
            triageForm.hidden = true;
            triageUrl = null;
            setStatus('Triagem concluída', autorizada ? 'success' : 'danger');
        } catch (error) {
            triageFeedback.textContent = error.message || 'Não foi possível salvar a triagem.';
        } finally {
            triageSubmit.disabled = false;
        }
    });

    startButton.addEventListener('click', startCamera);
    window.addEventListener('pagehide', stopCamera);
    startCamera();
})();
