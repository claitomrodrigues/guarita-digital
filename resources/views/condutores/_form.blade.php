@php($registro = $condutor ?? null)

<div class="form-grid">
    <label class="field-span-2">
        Nome completo *
        <input name="nome" value="{{ old('nome', $registro?->nome) }}" required maxlength="150">
    </label>

    <label>
        CPF
        <input name="cpf" value="{{ old('cpf', $registro?->cpf) }}" inputmode="numeric" maxlength="14" placeholder="000.000.000-00">
    </label>

    <label>
        Matrícula
        <input name="matricula" value="{{ old('matricula', $registro?->matricula) }}" maxlength="40">
    </label>

    <label>
        Tipo de vínculo *
        <select name="tipo_vinculo" required>
            <option value="">Selecione</option>
            @foreach ($tiposVinculo as $tipo)
                <option value="{{ $tipo->value }}" @selected(old('tipo_vinculo', $registro?->tipo_vinculo?->value) === $tipo->value)>
                    {{ $tipo->label() }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Telefone
        <input name="telefone" value="{{ old('telefone', $registro?->telefone) }}" maxlength="20">
    </label>

    <label class="field-span-2">
        E-mail
        <input name="email" type="email" value="{{ old('email', $registro?->email) }}" maxlength="150">
    </label>

    <label class="field-span-2">
        Observações
        <textarea name="observacoes" maxlength="1000">{{ old('observacoes', $registro?->observacoes) }}</textarea>
    </label>
</div>

<label class="check-row">
    <input name="ativo" type="checkbox" value="1" @checked(old('ativo', $registro?->ativo ?? true))>
    Condutor ativo
</label>

<div class="form-actions">
    <a class="btn btn-ghost" href="{{ route('condutores.index') }}">Cancelar</a>
    <button class="btn btn-primary" type="submit">Salvar condutor</button>
</div>
