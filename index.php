<?php

// Definições iniciais
session_start();

// Variáveis para mensagens (inicializadas vazias)
$mensagem = '';
$tipo_mensagem = '';

// Recupera valores do formulário da sessão (se existirem)
$valores_formulario = $_SESSION['valores_formulario'] ?? [];
unset($_SESSION['valores_formulario']);

// Define se o formulário de cadastro deve ser exibido devido a erros
$cadastro_ativo_devido_erro = $_SESSION['cadastro_ativo_devido_erro'] ?? false;
unset($_SESSION['cadastro_ativo_devido_erro']);

// Funções para evitar problemas de segurança
function sanitizar_entrada($dado) {
    $dado = trim($dado);
    $dado = stripslashes($dado);
    $dado = htmlspecialchars($dado);
    return $dado;
}

function escapar_html($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// Processamento do formulário (se enviado)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['valores_formulario'] = $_POST;

    if (isset($_POST['submit_login'])) {
        // Processamento do login
        $emailOuUsuario = sanitizar_entrada($_POST['emailOuUsuario'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!empty($emailOuUsuario) && !empty($senha)) {
            // Simulação de login bem-sucedido (em uma aplicação real, verifique no banco de dados)
            $mensagem = "Login efetuado com sucesso! Bem-vindo(a), " . escapar_html($emailOuUsuario) . ". (Simulado)";
            $tipo_mensagem = 'sucesso';
            unset($_SESSION['valores_formulario']);
        } else {
            $mensagem = "Falha ao logar: preencha todos os campos.";
            $tipo_mensagem = 'erro';
        }
    } elseif (isset($_POST['submit_registro'])) {
        // Processamento do cadastro
        $nome = sanitizar_entrada($_POST['nome'] ?? '');
        $whatsapp = sanitizar_entrada($_POST['whatsapp'] ?? '');
        $email = sanitizar_entrada($_POST['email'] ?? '');
        $usuario = sanitizar_entrada($_POST['usuario'] ?? '');
        $senha = $_POST['senha_registro'] ?? '';
        $validacao_passou = true;
        $mensagens_erro_sessao = [];

        // Validação dos campos
        if (empty($nome)) { $mensagens_erro_sessao['nome'] = "Nome é obrigatório."; $validacao_passou = false; }
        if (empty($whatsapp)) { $mensagens_erro_sessao['whatsapp'] = "WhatsApp é obrigatório."; $validacao_passou = false; }
        elseif (!preg_match('/^\d{10,11}$/', preg_replace('/\D/', '', $whatsapp))) { $mensagens_erro_sessao['whatsapp'] = "Formato inválido de WhatsApp."; $validacao_passou = false; }
        if (empty($email)) { $mensagens_erro_sessao['email'] = "Email é obrigatório."; $validacao_passou = false; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $mensagens_erro_sessao['email'] = "Formato inválido de email."; $validacao_passou = false;}
        if (empty($usuario)) { $mensagens_erro_sessao['usuario'] = "Nome de usuário é obrigatório."; $validacao_passou = false; }
        if (empty($senha)) { $mensagens_erro_sessao['senha_registro'] = "Senha é obrigatória."; $validacao_passou = false; }
        elseif (strlen($senha) < 6) { $mensagens_erro_sessao['senha_registro'] = "A senha deve ter pelo menos 6 caracteres."; $validacao_passou = false;}

        if ($validacao_passou) {
            // Simulação de cadastro bem-sucedido (em uma aplicação real, salve no banco de dados)
            $_SESSION['mensagem_sucesso_cadastro'] = "Cadastro realizado com sucesso para " . escapar_html($usuario) . "! Agora você pode fazer login.";
            unset($_SESSION['valores_formulario']);
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]) . "?mostrar_login=true#formLogin");
            exit;
        } else {
            $_SESSION['cadastro_ativo_devido_erro'] = true;
            $_SESSION['mensagem_erro_cadastro'] = "Falha no cadastro. Por favor, verifique os campos abaixo.";
            $_SESSION['erros_campos'] = $mensagens_erro_sessao;
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]) . "?mostrar_erro_cadastro=true#formCadastro");
            exit;
        }
    }
}

// Lidar com mensagens e estados após redirecionamento
if (isset($_GET['mostrar_login']) && isset($_SESSION['mensagem_sucesso_cadastro'])) {
    $mensagem = $_SESSION['mensagem_sucesso_cadastro'];
    $tipo_mensagem = 'sucesso';
    unset($_SESSION['mensagem_sucesso_cadastro']);
    $valores_formulario = [];
} elseif (isset($_GET['mostrar_erro_cadastro'])) {
    $mensagem = $_SESSION['mensagem_erro_cadastro'] ?? "Falha no cadastro. Por favor, verifique os campos.";
    $tipo_mensagem = 'erro';
    $cadastro_ativo_devido_erro = true;
    unset($_SESSION['mensagem_erro_cadastro']);
    unset($_SESSION['erros_campos']);
}

$anoAtual = date("Y");

// Determina a classe inicial do cartão (se deve mostrar o formulário de cadastro)
$classe_cartao_inicial = '';
if ($cadastro_ativo_devido_erro || isset($_GET['mostrar_erro_cadastro'])) {
    $classe_cartao_inicial = 'modo-cadastro';
} elseif (isset($_GET['mostrar_cadastro'])) {
    $classe_cartao_inicial = 'modo-cadastro';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EchoSphere Auth</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide-static@latest/umd/lucide.js"></script>
</head>
<body>
    <main class="container-autenticacao">
        <div class="perspectiva">
            <div class="cartao-flip <?php echo $classe_cartao_inicial; ?>">
                <div class="cartao-flip-interno">
                    <!-- Formulário de Login (Frente) -->
                    <div class="cartao-flip-frente cartao-formulario-autenticacao">
                        <div class="cabecalho-formulario">
                            <img src="https://picsum.photos/seed/echospherelogo/80/80" alt="Logo EchoSphere" class="logo" data-ai-hint="futuristic sphere logo"/>
                            <h2>Entrar</h2>
                            <p>Acesse sua conta para continuar.</p>
                        </div>
                        <?php if ($mensagem && $tipo_mensagem && !$cadastro_ativo_devido_erro && !isset($_GET['mostrar_erro_cadastro']) && !isset($_GET['mostrar_cadastro'])): ?>
                            <div class="mensagem <?php echo escapar_html($tipo_mensagem); ?>"><?php echo escapar_html($mensagem); ?></div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#formLogin" method="POST" id="formLogin" novalidate>
                            <div class="grupo-formulario">
                                <label for="loginEmailOuUsuario">Email ou Nome de Usuário</label>
                                <input type="text" id="loginEmailOuUsuario" name="emailOuUsuario" placeholder="seuemail@exemplo.com ou nomeusuario" required value="<?php echo escapar_html($valores_formulario['emailOuUsuario'] ?? ''); ?>">
                                <span class="mensagem-erro"></span>
                            </div>
                            <div class="grupo-formulario">
                                <label for="loginSenha">Senha</label>
                                <div class="campo-senha-wrapper">
                                    <input type="password" id="loginSenha" name="senha" placeholder="Sua senha" required>
                                    <button type="button" class="alternar-senha" aria-label="Alternar visibilidade da senha">
                                        <i data-lucide="eye-off"></i>
                                    </button>
                                </div>
                                <span class="mensagem-erro"></span>
                            </div>
                            <button type="submit" name="submit_login" class="botao-submit">
                                <i data-lucide="loader-2" class="spinner" style="display:none;"></i>
                                Entrar
                            </button>
                        </form>
                        <div class="alternar-modo">
                            <label for="checkbox-alternar-modo-frente">Não tem uma conta?</label>
                            <input type="checkbox" id="checkbox-alternar-modo-frente" class="checkbox-alternar-modo" aria-label="Mudar para formulário de cadastro">
                        </div>
                    </div>

                    <!-- Formulário de Cadastro (Verso) -->
                    <div class="cartao-flip-verso cartao-formulario-autenticacao">
                        <div class="cabecalho-formulario">
                           <img src="https://picsum.photos/seed/echospherelogo/80/80" alt="Logo EchoSphere" class="logo" data-ai-hint="futuristic sphere logo"/>
                            <h2>Criar Conta</h2>
                            <p>Preencha para se cadastrar.</p>
                        </div>
                         <?php if ($mensagem && $tipo_mensagem && ($cadastro_ativo_devido_erro || isset($_GET['mostrar_erro_cadastro']))): ?>
                            <div class="mensagem <?php echo escapar_html($tipo_mensagem); ?>"><?php echo escapar_html($mensagem); ?></div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#formCadastro" method="POST" id="formCadastro" novalidate>
                            <div class="grupo-formulario">
                                <label for="cadastroNome">Nome Completo</label>
                                <input type="text" id="cadastroNome" name="nome" placeholder="Seu nome completo" required minlength="2" value="<?php echo escapar_html($valores_formulario['nome'] ?? ''); ?>">
                                <span class="mensagem-erro"></span>
                            </div>
                            <div class="grupo-formulario">
                                <label for="cadastroWhatsapp">WhatsApp</label>
                                <input type="tel" id="cadastroWhatsapp" name="whatsapp" placeholder="(XX) XXXXX-XXXX" required value="<?php echo escapar_html($valores_formulario['whatsapp'] ?? ''); ?>">
                                <span class="mensagem-erro"></span>
                            </div>
                            <div class="grupo-formulario">
                                <label for="cadastroEmail">Email</label>
                                <input type="email" id="cadastroEmail" name="email" placeholder="seuemail@exemplo.com" required value="<?php echo escapar_html($valores_formulario['email'] ?? ''); ?>">
                                <span class="mensagem-erro"></span>
                            </div>
                            <div class="grupo-formulario">
                                <label for="cadastroUsuario">Nome de Usuário</label>
                                <input type="text" id="cadastroUsuario" name="usuario" placeholder="Escolha um nome de usuário" required minlength="3" value="<?php echo escapar_html($valores_formulario['usuario'] ?? ''); ?>">
                                <span class="mensagem-erro"></span>
                            </div>
                            <div class="grupo-formulario">
                                <label for="cadastroSenha">Senha</label>
                                 <div class="campo-senha-wrapper">
                                    <input type="password" id="cadastroSenha" name="senha_registro" placeholder="Crie uma senha segura" required minlength="6">
                                    <button type="button" class="alternar-senha" aria-label="Alternar visibilidade da senha">
                                         <i data-lucide="eye-off"></i>
                                    </button>
                                </div>
                                <span class="mensagem-erro"></span>
                            </div>
                            <button type="submit" name="submit_registro" class="botao-submit">
                                <i data-lucide="loader-2" class="spinner" style="display:none;"></i>
                                Cadastrar
                            </button>
                        </form>
                        <div class="alternar-modo">
                            <label for="checkbox-alternar-modo-verso">Já tem uma conta?</label>
                            <input type="checkbox" id="checkbox-alternar-modo-verso" class="checkbox-alternar-modo" aria-label="Mudar para formulário de login" checked>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer>
            <p>&copy; <?php echo $anoAtual; ?> EchoSphere Auth. Todos os direitos reservados.</p>
            <p>Inspirado na modernidade e inovação.</p>
        </footer>
    </main>
    <script src="script.js"></script>
    <script>
      lucide.createIcons(); // Cria os ícones do Lucide
    </script>
</body>
</html>
    
