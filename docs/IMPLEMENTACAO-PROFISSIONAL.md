# Implementação profissional do backend

Este pacote consolida a camada de domínio e a API do Guarita Digital para Laravel 13 e MySQL.

## Entregue

- Models Eloquent com casts, mutators, scopes e relacionamentos;
- Enums tipados para todos os estados do domínio;
- Policies para administrador e segurança da guarita;
- Form Requests com autorização, normalização e validações reutilizáveis;
- API Resources para respostas JSON estáveis;
- Services para registro de acesso, triagem e auditoria;
- prevenção de duplicidade e concorrência por lock e transação;
- reconhecimento de placas antigas e Mercosul;
- armazenamento privado de capturas;
- dashboard, histórico, filtros e paginação;
- auditoria automática de alterações administrativas;
- migrations, factories, seeders e testes de domínio;
- múltiplas contas de administrador e segurança;
- API autenticada para câmeras YOLO + PaddleOCR;
- triagens, idempotência por captura e relatório CSV.

## Usuários e câmeras

A coluna `users.perfil` aceita `administrador` ou `seguranca`, sem limitar a quantidade de contas. Administradores gerenciam usuários; cada câmera recebe um token próprio vinculado ao ponto de acesso. O backend impede a remoção do último administrador ativo.

## Validações realizadas na entrega

- sintaxe PHP em todos os arquivos da aplicação, rotas, configurações, migrations, seeders e testes;
- inicialização manual do kernel Laravel;
- conferência das rotas web e da API de câmeras;
- registro das Policies;
- testes diretos dos normalizadores de placa, CPF e opções dos Enums.

A execução completa de migrations e PHPUnit deve ser feita no Laragon, com `pdo_mysql`, `mbstring`, `dom`, `xml` e `xmlwriter` habilitados.
