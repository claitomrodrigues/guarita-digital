# Implementação profissional do backend

Este pacote consolida a camada de domínio e a API do Guarita Digital para Laravel 13 e MySQL.

## Entregue

- Models Eloquent com casts, mutators, scopes e relacionamentos;
- Enums tipados para todos os estados do domínio;
- Policies para administrador e segurança da guarita;
- Form Requests com autorização, normalização e validações reutilizáveis;
- API Resources para respostas JSON estáveis;
- Services para registro de acesso, auditoria e integração com Python;
- prevenção de duplicidade e concorrência por lock e transação;
- reconhecimento de placas antigas e Mercosul;
- armazenamento privado de capturas;
- dashboard, histórico, filtros e paginação;
- auditoria automática de alterações administrativas;
- migrations, factories, seeders e testes de domínio;
- duas contas fixas: administrador e segurança.

## Regra das duas contas

A coluna `users.perfil` é única e aceita somente `administrador` ou `seguranca`. Não existem rotas para criar ou excluir usuários. O administrador pode atualizar os dados das contas e o segurança pode alterar os próprios dados, sem trocar o perfil.

## Validações realizadas na entrega

- sintaxe PHP em todos os arquivos da aplicação, rotas, configurações, migrations, seeders e testes;
- inicialização manual do kernel Laravel;
- carregamento das 40 rotas;
- registro das Policies;
- testes diretos dos normalizadores de placa, CPF e opções dos Enums.

A execução completa de migrations e PHPUnit deve ser feita no Laragon, com `pdo_mysql`, `mbstring`, `dom`, `xml` e `xmlwriter` habilitados.
