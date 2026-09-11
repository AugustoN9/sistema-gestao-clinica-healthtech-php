# 🏥 Sistema de Gestão Clínica - HealthTech

> Sistema web completo desenvolvido em PHP nativo e MySQL para gerenciamento de clínicas, suporte a múltiplos perfis de usuários (Administradores, Médicos, Enfermagem e Pacientes), controle de consultas, exames, vacinação e painéis analíticos.

🔗 **Acesse a aplicação online:** [HealthTech no Render](https://sistema-gestao-clinica-healthtech-php.onrender.com)

---

## 🚀 Tecnologias Utilizadas

* **Linguagem:** PHP (Nativo)
* **Banco de Dados:** MySQL (Hospedado via nuvem)
* **Servidor Web / Container:** Apache HTTP Server via **Docker**
* **Frontend / Estilização:** Bootstrap 5, FontAwesome, Leaflet.js
* **Hospedagem & Deploy:** Render (Web Service)

---

## ✨ Principais Funcionalidades

* **Múltiplos Níveis de Acesso:** Painéis customizados para Administradores, Gestores, Médicos, Equipe de Enfermagem e Pacientes.
* **Gestão de Pacientes e Prontuários:** Cadastro detalhado, histórico de saúde, perfil vacinal e acompanhamento de consultas.
* **Módulo de Consultas e Exames:** Agendamento inteligente, controle de status, solicitação de exames e emissão de atestados/receitas.
* **Controle de Vacinação:** Calendário vacinal e registro de aplicações de doses pela equipe de enfermagem.
* **Painéis Analíticos (Dashboards):** Estatísticas avançadas de atendimento, consultas e campanhas de vacinação.

---

## ⚙️ Variáveis de Ambiente (Deploy)

Para o correto funcionamento da aplicação em ambiente de produção (ou Docker), as seguintes variáveis de ambiente devem ser configuradas:

* `DB_HOST` - Endereço do servidor MySQL
* `DB_NAME` - Nome do banco de dados
* `DB_USER` - Usuário de acesso ao banco
* `DB_PASS` - Senha de autenticação do banco

---

## 📂 Estrutura do Projeto

```text
├── admin/                              # Painéis e rotas administrativas
├── assets/                             # Imagens, folhas de estilo e recursos visuais
├── auth/                               # Liderança de login, registro e sessões
├── consultas/                          # Gestão, agendamento e detalhes de consultas
├── enfermagem/                         # Módulo de triagem e enfermagem
├── gerenciar/                          # Utilitários de disponibilidade e gestão
├── helpers/                            # Funções auxiliares (temas, regras de negócio)
├── medicos/                            # Gestão de médicos e agendas profissionais
├── pacientes/                          # Prontuários e histórico de pacientes
├── vacinas/                            # Cadastro e controle de imunização
├── acesso_negado_tela.php              # Tela de restrição de permissões
├── admin_painel.php                    # Painel de controle geral administrativo
├── agendamento_enfermagem.php          # Gestão de agendas da enfermagem
├── aplicar_vacina.php                  # Registro de aplicação de vacinas
├── area_enfermagem.php                 # Dashboard operacional da enfermagem
├── area_medico.php                     # Dashboard operacional do médico
├── area_paciente.php                   # Portal de acompanhamento do paciente
├── auth.php                            # Regras globais de autenticação e sessão
├── calendario_vacinal.php              # Visualização do calendário de vacinação
├── conexao.php                         # Script centralizado de conexão PDO/MySQLi
├── dashboard_consultas.php             # Painel estatístico de consultas médicas
├── Dockerfile                          # Configuração do container Docker (PHP 8.2 + Apache)
├── enfermagem_fila_triagem.php         # Fila de atendimento e triagem
├── estatisticas.php                    # Relatórios e métricas gerais do sistema
├── estatisticas_consultas.php          # Métricas específicas de agendamentos
├── footer.php                          # Rodapé dinâmico padrão
├── header.php                          # Cabeçalho e navbar dinâmica por perfil
├── index.php                           # Ponto de entrada e redirecionamento inicial
├── infovacinacao.php                   # Dashboard analítico de vacinação
├── painel_disponibilidade_medica.php   # Gestão de horários e agendas médicas
├── realizar_triagem.php                # Formulário de triagem de pacientes
├── remover_item_agendamento.php        # Lógica de exclusão de itens de agenda
├── salvar_agendamento_vacina.php       # Processamento de agendamento de doses
├── salvar_triagem.php                  # Salvamento dos dados de triagem
└── ...

````
## 👨‍💻 Autor   Desenvolvido por Augusto - Tópicos Avançados.
