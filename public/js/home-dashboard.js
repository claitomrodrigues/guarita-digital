(() => {
    const chart = document.getElementById('traffic-chart');
    const recentAccesses = document.getElementById('dashboard-recent-accesses');
    const status = document.getElementById('dashboard-refresh-status');
    const yardVehicles = document.getElementById('dashboard-yard-vehicles-list');
    const yardPanel = document.getElementById('dashboard-yard-vehicles');
    const yardFeedback = document.getElementById('dashboard-yard-feedback');
    const yardCount = document.getElementById('dashboard-yard-vehicle-count');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!chart || !recentAccesses || !status || !yardVehicles || !yardPanel) return;

    const numberFormat = new Intl.NumberFormat('pt-BR');
    let updating = false;
    let refreshPending = false;

    const updateYardVehicles = (vehicles) => {
        yardVehicles.replaceChildren();
        yardCount.textContent = numberFormat.format(vehicles.length);

        if (vehicles.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'empty';
            cell.textContent = 'Nenhum veículo no pátio.';
            row.append(cell);
            yardVehicles.append(row);
            return;
        }

        vehicles.forEach((vehicle) => {
            const row = document.createElement('tr');
            [vehicle.horario, vehicle.placa, vehicle.veiculo].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value ?? '';
                row.append(cell);
            });

            const actionCell = document.createElement('td');
            if (yardPanel.dataset.manualAccessUrl) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'yard-exit-button';
                button.dataset.plate = vehicle.placa;
                button.textContent = 'X';
                button.title = 'Registrar saída';
                button.setAttribute('aria-label', `Registrar saída da placa ${vehicle.placa}`);
                actionCell.append(button);
            } else {
                actionCell.textContent = '—';
            }

            row.append(actionCell);
            yardVehicles.append(row);
        });
    };

    const updateRecentAccesses = (accesses) => {
        recentAccesses.replaceChildren();

        if (accesses.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.className = 'empty';
            cell.textContent = 'Nenhuma movimentação registrada.';
            row.append(cell);
            recentAccesses.append(row);
            return;
        }

        accesses.forEach((access) => {
            const row = document.createElement('tr');
            const timeCell = document.createElement('td');
            const timeValue = document.createElement('strong');
            timeValue.textContent = access.horario;
            timeCell.append(timeValue);
            row.append(timeCell);

            [access.placa, access.veiculo].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value ?? '';
                row.append(cell);
            });

            const movementCell = document.createElement('td');
            const movement = document.createElement('span');
            movement.className = `pill ${access.tipo === 'entrada' ? 'success' : 'neutral'}`;
            movement.textContent = access.tipo === 'entrada' ? 'Entrada' : 'Saída';
            movementCell.append(movement);

            const statusCell = document.createElement('td');
            const accessStatus = document.createElement('span');
            accessStatus.className = `pill ${access.autorizado ? 'success' : 'danger'}`;
            accessStatus.textContent = access.autorizado ? 'Autorizado' : 'Pendente ou bloqueado';
            statusCell.append(accessStatus);

            row.append(movementCell, statusCell);
            recentAccesses.append(row);
        });
    };

    const updateChart = (hours) => {
        const columns = Array.from(chart.querySelectorAll('.traffic-column'));
        const maxEntries = Math.max(1, ...hours.map((item) => item.entradas));
        const maxExits = Math.max(1, ...hours.map((item) => item.saidas));

        hours.forEach((item, index) => {
            const column = columns[index];
            if (!column) return;

            const [entryBar, exitBar] = column.querySelectorAll('.traffic-bar');
            const entryHeight = item.entradas > 0 ? Math.max(5, Math.round(item.entradas / maxEntries * 100)) : 3;
            const exitHeight = item.saidas > 0 ? Math.max(5, Math.round(item.saidas / maxExits * 100)) : 3;

            entryBar.style.height = `${entryHeight}%`;
            exitBar.style.height = `${exitHeight}%`;
            column.title = `${item.hora}: ${item.entradas} entradas, ${item.saidas} saídas`;
        });
    };

    const refresh = async () => {
        if (document.hidden) return;
        if (updating) {
            refreshPending = true;
            return;
        }
        updating = true;

        try {
            const response = await fetch(chart.dataset.dashboardUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('Falha ao consultar o dashboard.');

            const data = await response.json();
            document.getElementById('dashboard-entries').textContent = numberFormat.format(data.totalEntradasHoje);
            document.getElementById('dashboard-exits').textContent = numberFormat.format(data.totalSaidasHoje);
            document.getElementById('dashboard-vehicles').textContent = numberFormat.format(data.totalVeiculos);
            document.getElementById('dashboard-authorized-vehicles').textContent = numberFormat.format(data.veiculosAutorizados);
            document.getElementById('dashboard-yard-count').textContent = `${numberFormat.format(data.veiculosNoPatio)} veículos`;
            document.getElementById('dashboard-peak-entry').textContent = data.picoEntrada;
            document.getElementById('dashboard-peak-exit').textContent = data.picoSaida;
            updateChart(data.movimentacoesPorHora);
            updateRecentAccesses(data.movimentacoesRecentes);
            updateYardVehicles(data.veiculosNoPatioLista);
            status.textContent = `Atualizado às ${new Date().toLocaleTimeString('pt-BR')}`;
        } catch {
            status.textContent = 'Não foi possível atualizar';
        } finally {
            updating = false;
            if (refreshPending) {
                refreshPending = false;
                void refresh();
            }
        }
    };

    yardVehicles.addEventListener('click', async (event) => {
        const button = event.target.closest('.yard-exit-button');
        if (!button || button.disabled) return;

        button.disabled = true;
        yardFeedback.textContent = `Registrando saída de ${button.dataset.plate}...`;

        try {
            const response = await fetch(yardPanel.dataset.manualAccessUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ placa: button.dataset.plate, tipo: 'saida' }),
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = Object.values(data.errors || {}).flat()[0];
                throw new Error(validationMessage || data.message || 'Não foi possível registrar a saída.');
            }

            yardFeedback.textContent = `Saída de ${button.dataset.plate} registrada.`;
            button.closest('tr')?.remove();
            if (yardVehicles.querySelectorAll('tr').length === 0) {
                updateYardVehicles([]);
            } else {
                yardCount.textContent = numberFormat.format(yardVehicles.querySelectorAll('tr').length);
            }
            await refresh();
        } catch (error) {
            yardFeedback.textContent = error.message || 'Não foi possível registrar a saída.';
            button.disabled = false;
        }
    });

    refresh();
    window.setInterval(refresh, 15000);
})();