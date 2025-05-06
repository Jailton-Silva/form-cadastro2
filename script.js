document.addEventListener('DOMContentLoaded', () => {
    // Seletores dos elementos do DOM
    const containerCartaoFlip = document.querySelector('.cartao-flip');
    const internoCartaoFlip = document.querySelector('.cartao-flip-interno');
    const checkboxesAlternarModo = document.querySelectorAll('.checkbox-alternar-modo');
    
    const formLoginEl = document.getElementById('formLogin');
    const formCadastroEl = document.getElementById('formCadastro');
    
    // Estado atual do modo (cadastro ou login)
    let modoCadastroAtual = containerCartaoFlip.classList.contains('modo-cadastro');

    // Função para sincronizar os checkboxes e o modo do cartão
    function sincronizarAlternadoresEModo(ehCadastro, interacaoUsuario = false) {
        modoCadastroAtual = ehCadastro;
        containerCartaoFlip.classList.toggle('modo-cadastro', ehCadastro);
        checkboxesAlternarModo.forEach(checkbox => {
            if (checkbox.checked !== ehCadastro) {
                checkbox.checked = ehCadastro;
            }
        });
        
        // Limpa formulários apenas se for uma interação do usuário, não na sincronização inicial da página
        if (interacaoUsuario) {
            const formParaLimpar = ehCadastro ? formLoginEl : formCadastroEl;
            const formAtivo = ehCadastro ? formCadastroEl : formLoginEl;
            
            if (formParaLimpar) {
                formParaLimpar.reset(); // Reseta o formulário
                // Limpa mensagens de erro e classes de inválido
                formParaLimpar.querySelectorAll('.mensagem-erro').forEach(span => span.textContent = '');
                formParaLimpar.querySelectorAll('input.invalido').forEach(input => input.classList.remove('invalido'));
                // Esconde a mensagem principal do formulário que está sendo desativado
                const mensagemTopoLimpar = formParaLimpar.closest('.cartao-formulario-autenticacao').querySelector('.mensagem');
                if (mensagemTopoLimpar) mensagemTopoLimpar.style.display = 'none';
            }
            
            if (formAtivo) {
                 // Mostra ou esconde a mensagem principal do formulário ativo com base no seu conteúdo
                const mensagemTopoAtivo = formAtivo.closest('.cartao-formulario-autenticacao').querySelector('.mensagem');
                 if (mensagemTopoAtivo && mensagemTopoAtivo.textContent.trim() === '') { 
                    mensagemTopoAtivo.style.display = 'none'; // Esconde se estiver vazia
                } else if (mensagemTopoAtivo) { 
                     mensagemTopoAtivo.style.display = 'block'; // Mostra se tiver conteúdo (provavelmente do PHP)
                 }
            }
        }
        ajustarAlturaCartao(); // Ajusta a altura do cartão após a mudança de modo
    }

    // Adiciona listeners aos checkboxes para alternar o modo
    checkboxesAlternarModo.forEach(checkboxEl => {
        checkboxEl.addEventListener('change', (e) => {
            sincronizarAlternadoresEModo(e.target.checked, true); // true indica interação do usuário
        });
    });
    
    // Inicializa os checkboxes com base no modoCadastroAtual (derivado da classe PHP)
    checkboxesAlternarModo.forEach(checkbox => checkbox.checked = modoCadastroAtual);


    // Função para ajustar a altura do cartão flip
    function ajustarAlturaCartao() {
        if (!internoCartaoFlip || !formLoginEl || !formCadastroEl) return;

        const cartaoFrente = formLoginEl.closest('.cartao-flip-frente');
        const cartaoVerso = formCadastroEl.closest('.cartao-flip-verso');
        
        // Calcula a altura do conteúdo de cada lado do cartão
        const alturaConteudoFrente = cartaoFrente.scrollHeight;
        const alturaConteudoVerso = cartaoVerso.scrollHeight;
        
        // Define a altura alvo com base no modo atual
        const alturaAlvo = modoCadastroAtual ? alturaConteudoVerso : alturaConteudoFrente;
        
        // Define uma altura mínima base para cada modo
        const alturaMinimaBase = modoCadastroAtual ? 680 : 490; // Ajustado para acomodar mais campos no cadastro
        const alturaFinal = Math.max(alturaAlvo, alturaMinimaBase); // Usa a maior entre a altura do conteúdo e a mínima

        // Aplica a altura ao container e ao interior do cartão
        containerCartaoFlip.style.minHeight = `${alturaFinal}px`;
        internoCartaoFlip.style.height = `${alturaFinal}px`;
    }
    
    // Ajusta a altura do cartão ao redimensionar a janela (com debounce)
    let timeoutRedimensionar;
    window.addEventListener('resize', () => {
        clearTimeout(timeoutRedimensionar);
        timeoutRedimensionar = setTimeout(ajustarAlturaCartao, 100);
    });

    // Chama ajustarAlturaCartao após um pequeno atraso para permitir a renderização completa,
    // especialmente importante se o PHP preencheu formulários ou mensagens de erro.
    window.onload = () => { // Usa window.onload para maior confiabilidade após o carregamento de todos os recursos
        setTimeout(ajustarAlturaCartao, 150); // Aumentado o delay para garantir renderização
    };
    if (document.readyState === "complete") { // Se já estiver carregado
        setTimeout(ajustarAlturaCartao, 150);
    }

    // Lógica para alternar a visibilidade da senha
    document.querySelectorAll('.alternar-senha').forEach(botao => {
        botao.addEventListener('click', () => {
            const campoSenha = botao.previousElementSibling; // O input de senha
            if (!campoSenha || campoSenha.tagName !== 'INPUT') return;
            const icone = botao.querySelector('i');
            if (campoSenha.type === 'password') {
                campoSenha.type = 'text';
                if (icone) icone.setAttribute('data-lucide', 'eye');
            } else {
                campoSenha.type = 'password';
                if (icone) icone.setAttribute('data-lucide', 'eye-off');
            }
            if (window.lucide) lucide.createIcons(); // Recria os ícones para atualizar a mudança
        });
    });

    // Formatação e validação do campo WhatsApp
    const campoWhatsappCadastro = document.getElementById('cadastroWhatsapp');
    if (campoWhatsappCadastro) {
        // Dispara o evento 'input' para formatar o valor inicial, se houver
        if (campoWhatsappCadastro.value) {
             campoWhatsappCadastro.dispatchEvent(new Event('input', { bubbles: true }));
        }

        campoWhatsappCadastro.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, ''); // Remove tudo que não for dígito
            let valorFormatado = '';
            const tam = valor.length;

            if (tam === 0) {
                e.target.value = '';
                validarCampo(e.target); // Revalida o campo (mostrará erro de obrigatório, se for o caso)
                return;
            }

            // Formatação: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
            if (tam <= 2) {
                valorFormatado = `(${valor}`;
            } else if (tam <= 6) { // (XX) XXXX
                valorFormatado = `(${valor.substring(0, 2)}) ${valor.substring(2)}`;
            } else if (tam <= 10) { // (XX) XXXX-XXXX
                valorFormatado = `(${valor.substring(0, 2)}) ${valor.substring(2, 6)}-${valor.substring(6)}`;
            } else { // (XX) XXXXX-XXXX (para números com 9º dígito)
                valorFormatado = `(${valor.substring(0, 2)}) ${valor.substring(2, 7)}-${valor.substring(7, 11)}`;
            }
            
            e.target.value = valorFormatado;
            validarCampo(e.target); // Valida o campo após a formatação
        });
    }
    
    // Função para validar um campo individualmente
    function validarCampo(input) {
        const grupoFormulario = input.closest('.grupo-formulario');
        if (!grupoFormulario) return true;
        const spanErro = grupoFormulario.querySelector('.mensagem-erro');
        if (!spanErro) return true;

        let mensagem = '';
        input.classList.remove('invalido'); // Remove classe de inválido por padrão

        // Validações
        if (input.hasAttribute('required') && !input.value.trim()) {
            mensagem = 'Este campo é obrigatório.';
        } else if (input.type === 'email' && input.value.trim() && !/^\S+@\S+\.\S+$/.test(input.value)) {
            mensagem = 'Por favor, insira um endereço de email válido.';
        } else if (input.hasAttribute('minlength') && input.value.trim().length > 0 && input.value.trim().length < parseInt(input.getAttribute('minlength'))) {
            // Verifica minlength apenas se houver alguma entrada, para evitar conflito com "required"
            mensagem = `Deve ter pelo menos ${input.getAttribute('minlength')} caracteres.`;
        } else if (input.id === 'cadastroWhatsapp' && input.value.trim()) {
            const digitos = input.value.replace(/\D/g, '');
            // Valida se tem 10 ou 11 dígitos (após remover formatação)
            if (digitos.length > 0 && (digitos.length < 10 || digitos.length > 11)) { 
                 mensagem = 'O WhatsApp deve ter 10 ou 11 dígitos.';
            }
        }
        
        spanErro.textContent = mensagem; // Exibe a mensagem de erro
        if (mensagem) {
            input.classList.add('invalido'); // Adiciona classe de inválido
            return false; // Inválido
        }
        return true; // Válido
    }

    // Função para validar um formulário inteiro
    function validarFormulario(form) {
        let ehValido = true;
        // Valida todos os inputs que têm atributos de validação
        form.querySelectorAll('input[required], input[type="email"], input[minlength], input[id="cadastroWhatsapp"]').forEach(input => {
            if (!validarCampo(input)) { 
                ehValido = false;
            }
        });
        return ehValido;
    }
    
    // Adiciona listeners de validação aos formulários e seus campos
    [formLoginEl, formCadastroEl].forEach(form => {
        if (form) {
            // Validação inicial para formulários pré-preenchidos (ex: após redirecionamento de erro do PHP)
            validarFormulario(form); 
            
            form.querySelectorAll('input').forEach(input => {
                // Valida no evento 'blur' (perda de foco)
                input.addEventListener('blur', () => validarCampo(input));
                // Limpa erro ao digitar (ou revalida se for obrigatório e preenchido)
                input.addEventListener('input', () => {
                    const spanErro = input.closest('.grupo-formulario')?.querySelector('.mensagem-erro');
                    if (spanErro?.textContent && (input.value.trim() || !input.hasAttribute('required'))) { 
                        spanErro.textContent = '';
                        input.classList.remove('invalido');
                    } else if (input.hasAttribute('required') && input.value.trim()){
                         validarCampo(input); // Revalida se era obrigatório e agora tem conteúdo
                    }
                });
            });

            // Adiciona listener para o evento 'submit' do formulário
            form.addEventListener('submit', function(event) {
                const spinner = this.querySelector('.botao-submit .spinner');
                const botaoSubmit = this.querySelector('.botao-submit');

                if (!validarFormulario(this)) {
                    event.preventDefault(); // Impede o envio se a validação falhar
                } else {
                    // Mostra spinner e desabilita botão ao enviar
                    if (spinner) spinner.style.display = 'inline-block';
                    if (botaoSubmit) botaoSubmit.disabled = true;
                }
            });
        }
    });
    
    // Sincronização final do estado com base na URL ou classe PHP
    const paramsUrl = new URLSearchParams(window.location.search);
    if (paramsUrl.has('mostrar_login')) {
        sincronizarAlternadoresEModo(false, false); // false para interacaoUsuario
    } else if (paramsUrl.has('mostrar_erro_cadastro') || paramsUrl.has('mostrar_cadastro')) {
        sincronizarAlternadoresEModo(true, false);
    } else {
        // Se não houver parâmetros na URL, respeita a classe definida pelo PHP (já em modoCadastroAtual)
        sincronizarAlternadoresEModo(modoCadastroAtual, false);
    }
    
    // Garante que a altura do cartão esteja correta após toda a lógica JS inicial e possíveis exibições de mensagens
    ajustarAlturaCartao(); 
});
