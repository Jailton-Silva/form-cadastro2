<?php
session_start(); // Inicia a sessão

// Inicializa as variáveis de mensagem
$mensagem = ''; // Mensagem a ser exibida para o usuário
$tipo_mensagem = ''; // Tipo da mensagem: 'sucesso' ou 'erro'

// Recupera os valores do formulário da sessão, se existirem (ex: após um redirecionamento por falha na validação)
$valores_formulario = $_SESSION['valores_formulario'] ?? [];
unset($_SESSION['valores_formulario']); // Limpa após o uso

// Determina se o cartão deve ser inicialmente virado para o lado do cadastro devido a um erro anterior
$cadastro_ativo_devido_erro = $_SESSION['cadastro_ativo_devido_erro'] ?? false;
unset($_SESSION['cadastro_ativo_devido_erro']); // Limpa após o uso


// Função para sanitizar os dados de entrada
function sanitizar_entrada($dado) {
    $dado = trim($dado); // Remove espaços em branco no início e no fim
    $dado = stripslashes($dado); // Remove barras invertidas
    $dado = htmlspecialchars($dado); // Converte caracteres especiais em entidades HTML
    return $dado;
}

// Função auxiliar para exibir strings HTML seguras
function escapar_html($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// Verifica se o método da requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['valores_formulario'] = $_POST; // Armazena os dados POST atuais para possível repreenchimento em redirecionamento

    // Se o formulário de login foi enviado
    if (isset($_POST['submit_login'])) {
        $emailOuUsuario = sanitizar_entrada($_POST['emailOuUsuario'] ?? '');
        $senha = $_POST['senha'] ?? ''; // Senha não é sanitizada com htmlspecialchars para permitir caracteres especiais

        if (!empty($emailOuUsuario) && !empty($senha)) {
            // Simula login bem-sucedido
            // Em uma aplicação real, você verificaria as credenciais contra um banco de dados
            $mensagem = "Login bem-sucedido! Bem-vindo(a), " . escapar_html($emailOuUsuario) . ". (Simulado)";
            $tipo_mensagem = 'sucesso';
            unset($_SESSION['valores_formulario']); // Limpa os valores do formulário da sessão
        } else {
            $mensagem = "Falha no login: Por favor, preencha todos os campos.";
            $tipo_mensagem = 'erro';
            // Em caso de erro de login, permanecemos no lado do login
        }
    } elseif (isset($_POST['submit_registro'])) { // Se o formulário de cadastro foi enviado
        $nome = sanitizar_entrada($_POST['nome'] ?? '');
        $whatsapp = sanitizar_entrada($_POST['whatsapp'] ?? '');
        $email = sanitizar_entrada($_POST['email'] ?? '');
        $usuario = sanitizar_entrada($_POST['usuario'] ?? '');
        $senha = $_POST['senha_registro'] ?? ''; // Nome diferente para não colidir com a senha do login no POST
        $validacao_passou = true;
        $mensagens_erro_sessao = []; // Para armazenar erros específicos dos campos, se necessário posteriormente

        // Validação do lado do servidor
        if (empty($nome)) { $mensagens_erro_sessao['nome'] = "Nome é obrigatório."; $validacao_passou = false; }
        if (empty($whatsapp)) { $mensagens_erro_sessao['whatsapp'] = "WhatsApp é obrigatório."; $validacao_passou = false; }
        // Validação básica do WhatsApp (apenas dígitos, 10 ou 11 dígitos) - pode ser mais complexa
        elseif (!preg_match('/^\d{10,11}$/', preg_replace('/\D/', '', $whatsapp))) { $mensagens_erro_sessao['whatsapp'] = "Formato inválido de WhatsApp."; $validacao_passou = false; }
        if (empty($email)) { $mensagens_erro_sessao['email'] = "Email é obrigatório."; $validacao_passou = false; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $mensagens_erro_sessao['email'] = "Formato inválido de email."; $validacao_passou = false;}
        if (empty($usuario)) { $mensagens_erro_sessao['usuario'] = "Nome de usuário é obrigatório."; $validacao_passou = false; }
        if (empty($senha)) { $mensagens_erro_sessao['senha_registro'] = "Senha é obrigatória."; $validacao_passou = false; }
        elseif (strlen($senha) < 6) { $mensagens_erro_sessao['senha_registro'] = "A senha deve ter pelo menos 6 caracteres."; $validacao_passou = false;}
        
        if ($validacao_passou) {
            // Simula cadastro bem-sucedido
            // Em uma aplicação real, você salvaria os dados do usuário em um banco de dados
            $_SESSION['mensagem_sucesso_cadastro'] = "Cadastro realizado com sucesso para " . escapar_html($usuario) . "! Agora você pode fazer login.";
            unset($_SESSION['valores_formulario']); 
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]) . "?mostrar_login=true#formLogin"); 
            exit;
        } else {
            $_SESSION['cadastro_ativo_devido_erro'] = true; 
            $_SESSION['mensagem_erro_cadastro'] = "Falha no cadastro. Por favor, verifique os campos abaixo.";
            $_SESSION['erros_campos'] = $mensagens_erro_sessao; // Armazena erros específicos
            // $valores_formulario já estão em $_SESSION['valores_formulario'] para repreenchimento
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]) . "?mostrar_erro_cadastro=true#formCadastro");
            exit;
        }
    }
}

// Lida com mensagens e estados após redirecionamento do cadastro
if (isset($_GET['mostrar_login']) && isset($_SESSION['mensagem_sucesso_cadastro'])) {
    $mensagem = $_SESSION['mensagem_sucesso_cadastro'];
    $tipo_mensagem = 'sucesso';
    unset($_SESSION['mensagem_sucesso_cadastro']);
    $valores_formulario = []; // Limpa os valores do formulário para a página de login após cadastro bem-sucedido
} elseif (isset($_GET['mostrar_erro_cadastro'])) {
    $mensagem = $_SESSION['mensagem_erro_cadastro'] ?? "Falha no cadastro. Por favor, verifique os campos.";
    $tipo_mensagem = 'erro';
    $cadastro_ativo_devido_erro = true; // Garante que o JS vire para o cadastro
    // Erros específicos dos campos não são usados diretamente para exibição aqui, mas foram definidos para a sessão e $valores_formulario.
    // O JavaScript lida com mensagens de erro individuais dos campos com base nos valores de entrada no recarregamento do cliente.
    unset($_SESSION['mensagem_erro_cadastro']);
    // $valores_formulario já estão preenchidos pela sessão no topo do arquivo
    unset($_SESSION['erros_campos']); 
}


$anoAtual = date("Y"); // Obtém o ano atual
// Determina a classe inicial para o cartão flip
$classe_cartao_inicial = '';
if ($cadastro_ativo_devido_erro || isset($_GET['mostrar_erro_cadastro'])) {
    $classe_cartao_inicial = 'modo-cadastro'; // Classe para ativar o modo de cadastro
} elseif (isset($_GET['mostrar_cadastro'])) { // Permite forçar o modo de cadastro via GET para JS, se não for um caso de erro
    $classe_cartao_inicial = 'modo-cadastro';
}

// Inclui o template HTML
require 'index.html';
?>
