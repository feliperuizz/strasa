<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-ink-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Termos de Serviço - strasa</title>
    <link rel="icon" type="image/png" href="{{ asset('strasafavicon2.png') }}?v={{ time() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ink: { 900: '#1e1e1e', 800: '#2a2b2d', 700: '#363638', 600: '#454545', 500: '#6b6b6b' },
                        brand: { 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7' }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full bg-ink-900 text-slate-200 antialiased p-8">
    <div class="max-w-3xl mx-auto bg-ink-800 border border-ink-700 rounded-2xl p-8 shadow-xl">
        <div class="mb-8 flex justify-center">
            <a href="{{ url('/') }}"><img src="{{ asset('strasalogo.png') }}" alt="strasa Logo" class="h-12 brightness-0 invert opacity-90"></a>
        </div>
        
        <h1 class="text-3xl font-bold text-white mb-6">Termos de Serviço</h1>
        
        <div class="space-y-6 text-slate-300 leading-relaxed">
            <p><strong>Última atualização:</strong> {{ now()->format('d/m/Y') }}</p>
            
            <h2 class="text-xl font-semibold text-white mt-8">1. Aceitação dos Termos</h2>
            <p>Ao acessar e utilizar o aplicativo interno <strong>strasa</strong>, provido pela Consultoria STR, você concorda em cumprir estes Termos de Serviço, todas as leis e regulamentos aplicáveis, e concorda que é responsável pelo cumprimento de quaisquer leis locais aplicáveis. Se você não concordar com algum destes termos, está proibido de usar ou acessar este sistema.</p>

            <h2 class="text-xl font-semibold text-white mt-8">2. Uso da Plataforma</h2>
            <p>O <strong>strasa</strong> é um sistema fechado e de uso interno. Seu acesso é restrito aos colaboradores, clientes e parceiros autorizados da Consultoria STR. É estritamente proibido compartilhar credenciais de acesso, realizar engenharia reversa no código do sistema ou tentar acessar dados para os quais você não possui autorização explícita.</p>

            <h2 class="text-xl font-semibold text-white mt-8">3. Integração com o Google Drive</h2>
            <p>Como parte de suas funcionalidades, o sistema oferece integração com o Google Drive para armazenamento de arquivos e anexos. Ao autorizar o aplicativo na tela de consentimento do Google (OAuth), você concorda que o sistema fará o upload e a gestão de arquivos nas pastas do projeto. O uso da integração deve ocorrer exclusivamente para os propósitos profissionais estabelecidos pela empresa.</p>

            <h2 class="text-xl font-semibold text-white mt-8">4. Isenção de Responsabilidade</h2>
            <p>O sistema é fornecido "no estado em que se encontra", sem garantias de qualquer tipo, expressas ou implícitas. A Consultoria STR não garante que o sistema será ininterrupto ou livre de erros, embora todos os esforços sejam feitos para manter a alta disponibilidade e a segurança dos dados.</p>

            <h2 class="text-xl font-semibold text-white mt-8">5. Modificações</h2>
            <p>A Consultoria STR pode revisar estes termos de serviço a qualquer momento, sem aviso prévio. Ao usar este aplicativo, você concorda em ficar vinculado à versão atual desses Termos de Serviço.</p>

            <h2 class="text-xl font-semibold text-white mt-8">6. Contato</h2>
            <p>Para dúvidas e suporte em relação a estes termos, entre em contato através de: <a href="mailto:contato@consultoriastr.com.br" class="text-brand-400 hover:underline">contato@consultoriastr.com.br</a>.</p>
        </div>
        
        <div class="mt-12 pt-6 border-t border-ink-700 text-center">
            <a href="{{ url('/') }}" class="text-brand-500 hover:text-brand-400 font-medium">&larr; Voltar para a página inicial</a>
        </div>
    </div>
</body>
</html>
