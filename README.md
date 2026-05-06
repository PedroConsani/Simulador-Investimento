# Plataforma de Investimentos

Uma aplicação web PHP para gerenciamento de investimentos em ações, com funcionalidades de compra/venda, portfólio e histórico de transações.

## Estrutura do Projeto

```
investimento/
├── config/
│   ├── config.php          # Configurações da API e constantes
│   └── database.php        # Conexão com banco de dados MySQL
├── data/
│   ├── dados_*.json        # Dados em cache da API
│   └── utilizadores.json   # Dados de usuários (legado)
├── public/
│   ├── index.php           # Página inicial
│   ├── login.php           # Login de usuários
│   ├── register.php        # Registro de usuários
│   ├── dashboard.php       # Dashboard do portfólio
│   ├── logout.php          # Logout
│   └── navbar.php          # Barra de navegação
├── src/
│   ├── controllers/
│   │   └── servico_transacao.php  # Serviço centralizado de transações
│   ├── Model/
│   │   ├── utilizador.php         # Modelo de usuário
│   │   ├── historico.php          # Modelo de histórico
│   │   ├── paper_tr_real.php      # Modelo de papel (ação)
│   │   ├── detalhes_paper_tr_real.php  # Detalhes da ação
│   │   ├── cacheJson.php          # Cache de dados JSON
│   │   └── admin.php              # Modelo de administrador
│   └── View/
│       ├── portifolio.php         # Visualização do portfólio
│       └── transacao.php          # Visualização de transações
└── README.md
```

## Funcionalidades

### ✅ Implementadas
- **Autenticação de Usuários**: Login e registro com validação
- **Dashboard de Portfólio**: Visualização de ações compradas e saldo
- **Compra/Venda de Ações**: Integração com API do Financial Modeling Prep
- **Histórico de Transações**: Registro completo de todas as operações
- **Prevenção de Duplicatas**: Pattern Post-Redirect-Get para evitar vendas duplicadas
- **Serviço Centralizado**: Toda lógica de transações em `ServicoTransacao`
- **Validação Robusta**: Verificação de saldo, quantidade e dados de entrada
- **Cache de Dados**: Otimização de chamadas à API

### 🔧 Configuração

1. **Banco de Dados MySQL**:
   ```sql
   CREATE DATABASE investimento;
   -- As tabelas são criadas automaticamente pelo código
   ```

2. **Configurações**:
   - Edite `config/config.php` com sua chave da API do Financial Modeling Prep
   - Configure as credenciais do banco em `config/database.php`

3. **Dependências**:
   - PHP 7.4+
   - MySQL 5.7+
   - Extensões: PDO, cURL

## Uso do Serviço de Transações

### Classe `ServicoTransacao`

Centraliza toda a lógica de compra e venda de ações.

#### Métodos Principais

```php
// Processa compra de ações
$resultado = ServicoTransacao::processarCompra(
    string $username,
    string $symbol,
    string $nomeEmpresa,
    float $quantidade,
    float $preco
);

// Processa venda de ações
$resultado = ServicoTransacao::processarVenda(
    string $username,
    string $symbol,
    float $quantidade,
    float $preco
);

// Valida transação antes de processar
$validacao = ServicoTransacao::validarTransacao(
    string $tipo,      // 'compra' ou 'venda'
    string $username,
    string $symbol,
    float $quantidade,
    float $preco
);

// Obtém informações da ação via API
$info = ServicoTransacao::obterInfoAcao(string $symbol);
```

#### Estrutura de Resposta

Todos os métodos retornam um array com:
```php
[
    'success' => bool,        // true se sucesso, false se erro
    'message' => string,      // mensagem descritiva
    'data' => array,          // dados adicionais (opcional)
    'erros' => array          // lista de erros (se houver)
]
```

### Exemplo de Uso em Controlador

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
    $symbol = $_POST['symbol'];
    $quantidade = floatval($_POST['quantidade']);
    $preco = floatval($_POST['preco']);
    $username = $_SESSION['utilizador']->getUsername();

    // Valida primeiro
    $validacao = ServicoTransacao::validarTransacao('compra', $username, $symbol, $quantidade, $preco);

    if (!$validacao['valido']) {
        // Mostra erros
        foreach ($validacao['erros'] as $erro) {
            echo "<p>Erro: $erro</p>";
        }
        return;
    }

    // Processa a compra
    $resultado = ServicoTransacao::processarCompra($username, $symbol, '', $quantidade, $preco);

    if ($resultado['success']) {
        // Atualiza sessão e redireciona
        $dadosAtualizados = Utilizador::findUser($username);
        $_SESSION['utilizador'] = new Utilizador($dadosAtualizados['username'], '', $dadosAtualizados['saldo']);

        header('Location: dashboard.php?success=compra');
        exit;
    } else {
        echo "<p>Erro: " . $resultado['message'] . "</p>";
    }
}
```

## Padrões Implementados

### Post-Redirect-Get (PRG)
Previne duplicação de transações ao recarregar a página:
```php
// Após processar POST, redireciona
header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
exit;
```

### Singleton para Conexão DB
Garante uma única conexão com o banco:
```php
class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new PDO(...);
        }
        return self::$instance;
    }
}
```

### Validação Centralizada
Todas as validações acontecem no serviço antes do processamento.

## API Externa

- **Financial Modeling Prep**: Dados em tempo real de ações
- **Cache**: Dados armazenados em JSON para reduzir chamadas à API
- **Fallback**: Sistema continua funcionando mesmo se API estiver indisponível

## Segurança

- **Prepared Statements**: Prevenção de SQL injection
- **Validação de Entrada**: Sanitização de todos os dados do usuário
- **Sessões Seguras**: Controle de sessão para autenticação
- **Prevenção de Duplicatas**: Pattern PRG para formulários

## Desenvolvimento

### Adicionando Novos Métodos
1. Adicione métodos estáticos à classe `ServicoTransacao`
2. Implemente validação no método `validarTransacao()`
3. Atualize os controladores para usar o novo método
4. Teste thoroughly

### Debugging
- Verifique logs de erro do PHP
- Use `var_dump()` para debug (remova em produção)
- Teste validações separadamente

## Arquivos de Exemplo

- `exemplo_servico_transacao.php`: Demonstra uso completo da classe `ServicoTransacao`

## Próximos Passos

- [ ] Implementar limites de transação
- [ ] Adicionar gráficos de performance
- [ ] Sistema de notificações
- [ ] API REST para mobile
- [ ] Testes automatizados

---

**Nota**: Este projeto é para fins educacionais. Feito com ajuda de inteligência artificial e verificado/analisado por mim (Pedro).
