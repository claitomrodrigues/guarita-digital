@php($registro = $veiculo ?? null)

@if ($condutores->isEmpty())
    <div class="alert error">
        Cadastre um condutor antes de cadastrar o veículo.
        <a href="{{ route('condutores.create') }}">Cadastrar condutor</a>
    </div>
@endif

<div class="form-grid">
    <label class="field-span-2">
        Condutor *
        <select name="pessoa_id" required>
            <option value="">Selecione</option>
            @foreach ($condutores as $condutor)
                <option value="{{ $condutor->id }}" @selected((string) old('pessoa_id', $registro?->pessoa_id) === (string) $condutor->id)>
                    {{ $condutor->nome }}{{ $condutor->matricula ? ' — '.$condutor->matricula : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Placa *
        <input name="placa" value="{{ old('placa', $registro?->placa) }}" required maxlength="8" placeholder="ABC1D23">
    </label>

    <label>
        Tipo *
        <select name="tipo" required>
            <option value="">Selecione</option>
            @foreach ($tiposVeiculo as $tipo)
                <option value="{{ $tipo->value }}" @selected(old('tipo', $registro?->tipo?->value) === $tipo->value)>
                    {{ $tipo->label() }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Marca
        <input name="marca" value="{{ old('marca', $registro?->marca) }}" maxlength="80" placeholder="Ex.: Volkswagen">
    </label>

    <label>
        Modelo *
        <input name="modelo" value="{{ old('modelo', $registro?->modelo) }}" required maxlength="100" placeholder="Ex.: Gol">
    </label>

    <label>
        Cor
        <input name="cor" value="{{ old('cor', $registro?->cor) }}" maxlength="50">
    </label>

    <label>
        Ano
        <input name="ano" type="number" value="{{ old('ano', $registro?->ano) }}" min="1900" max="{{ now()->year + 1 }}">
    </label>

    <label class="field-span-2">
        Observações
        <textarea name="observacoes" maxlength="1000">{{ old('observacoes', $registro?->observacoes) }}</textarea>
    </label>
</div>

<div class="check-group">
    <label class="check-row">
        <input name="ativo" type="checkbox" value="1" @checked(old('ativo', $registro?->ativo ?? true))>
        Veículo ativo
    </label>
    <label class="check-row">
        <input name="autorizado" type="checkbox" value="1" @checked(old('autorizado', $registro?->autorizado ?? true))>
        Acesso autorizado
    </label>
</div>

<div class="form-actions">
    <a class="btn btn-ghost" href="{{ route('veiculos.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit" @disabled($condutores->isEmpty())>Salvar veículo</button>
</div>
