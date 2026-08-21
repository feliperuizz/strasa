<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-ink-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidade - strasa</title>
    <link rel="icon" type="image/png" href="{{ asset('strasafavicon.png') }}">
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
        
        <h1 class="text-3xl font-bold text-white mb-6">Política de Privacidade</h1>
        
        <div class="space-y-6 text-slate-300 leading-relaxed">
            <p><strong>Última atualização:</strong> {{ now()->format('d/m/Y') }}</p>
            
            <h2 class="text-xl font-semibold text-white mt-8">1. Sobre o Aplicativo</h2>
            <p>O aplicativo <strong>strasa</strong> é um sistema de uso estritamente interno da Consultoria STR para a gestão de projetos e documentos. Esta Política de Privacidade descreve como coletamos, usamos e lidamos com suas informações e dados ao utilizar nossa plataforma e sua integração com o Google Drive.</p>
            
            <h2 class="text-xl font-semibold text-white mt-8">2. Acesso e Uso de Dados do Google (Google Workspace APIs)</h2>
            <p>O <strong>strasa</strong> solicita acesso ao seu Google Drive com a finalidade exclusiva de fazer o upload, leitura e gerenciamento de arquivos (anexos e documentos) relacionados aos projetos internos geridos no sistema.</p>
            <ul class="list-disc pl-6 space-y-2">
                <li>O aplicativo apenas acessa e gerencia os arquivos que são diretamente submetidos ou criados através da própria plataforma.</li>
                <li>Não lemos, acessamos ou modificamos arquivos pessoais do seu Google Drive que não estejam vinculados ao fluxo de trabalho do strasa.</li>
            </ul>

            <h2 class="text-xl font-semibold text-white mt-8">3. Armazenamento e Compartilhamento de Dados</h2>
            <p>Os dados processados através da integração com o Google Drive são mantidos nos servidores do Google, e apenas referências (IDs e links de visualização) são salvas em nosso banco de dados seguro para permitir a correta renderização na interface do sistema.</p>
            <p><strong>Nós não vendemos, alugamos ou compartilhamos</strong> seus dados, arquivos ou informações pessoais com terceiros sob nenhuma circunstância. O acesso ao sistema é restrito apenas a membros autorizados da equipe.</p>

            <h2 class="text-xl font-semibold text-white mt-8">4. Retenção de Dados e Revogação de Acesso</h2>
            <p>Os arquivos e dados associados aos projetos são retidos enquanto forem necessários para os fins operacionais da empresa. Você pode revogar o acesso do aplicativo à sua conta do Google a qualquer momento através do painel de configurações de segurança e permissões da sua Conta Google (<a href="https://myaccount.google.com/permissions" class="text-brand-400 hover:underline">myaccount.google.com/permissions</a>).</p>

            <h2 class="text-xl font-semibold text-white mt-8">5. Contato</h2>
            <p>Se você tiver alguma dúvida sobre esta Política de Privacidade, entre em contato conosco através do e-mail: <a href="mailto:contato@consultoriastr.com.br" class="text-brand-400 hover:underline">contato@consultoriastr.com.br</a>.</p>
        </div>
        
        <div class="mt-12 pt-6 border-t border-ink-700 text-center">
            <a href="{{ url('/') }}" class="text-brand-500 hover:text-brand-400 font-medium">&larr; Voltar para a página inicial</a>
        </div>
    </div>
</body>
</html>
