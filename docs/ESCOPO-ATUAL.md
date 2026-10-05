# Escopo atual

O backend foi reduzido ao fluxo necessário para a primeira demonstração:

1. autenticação por sessão;
2. Home;
3. CRUD de condutores;
4. CRUD de veículos;
5. vínculo entre condutor e veículo;
6. captura da webcam e reconhecimento local da placa em Python.

A câmera é aberta diretamente pelo navegador na Home. Ao clicar no botão de
captura, o JavaScript envia uma imagem Base64 em JSON para uma rota interna do
Laravel. Essa rota exige login, cria um arquivo temporário e executa
`python/reconhecer_imagem.py`. O script devolve um JSON ASCII com a placa ou o
motivo da falha. A imagem temporária é apagada no final.

Não há API externa, token ou cliente HTTP para câmeras nesta versão.

## Rotas principais

| Método | Rota | Finalidade |
| --- | --- | --- |
| GET/POST | `/login` | Exibir e processar o login |
| GET | `/home` | Exibir o resumo e a câmera |
| POST | `/camera/reconhecer` | Processar a captura da Home autenticada |
| Resource | `/condutores` | Cadastrar e gerenciar condutores |
| Resource | `/veiculos` | Cadastrar e gerenciar veículos |
| POST | `/logout` | Encerrar a sessão |
