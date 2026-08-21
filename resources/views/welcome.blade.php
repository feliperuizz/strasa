<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-ink-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>STRASA - Gestão de Documentos</title>
    <link rel="icon" type="image/png" href="{{ asset('strasafavicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ink: {
                            900: '#1e1e1e',
                            800: '#2a2b2d',
                            700: '#363638',
                            600: '#454545',
                            500: '#6b6b6b',
                        },
                        brand: {
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center text-slate-200 antialiased selection:bg-brand-500 selection:text-white">
    <div class="max-w-2xl mx-auto px-6 py-12 text-center">
        <div class="mb-8 flex justify-center">
            <img src="{{ asset('strasalogo.png') }}" alt="STRASA Logo" class="h-16 brightness-0 invert opacity-90">
        </div>
        
        <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">
            Gestão inteligente de projetos
        </h1>
        
        <div class="bg-ink-800 border border-ink-700 rounded-2xl p-8 mb-8 shadow-xl">
            <h2 class="text-xl font-semibold text-white mb-4">Sobre o STRASA</h2>
            <p class="text-lg leading-relaxed text-slate-300 mb-4">
                O STRASA é um sistema de uso exclusivamente interno desenvolvido para a gestão de documentos e controle de fluxo de trabalho da nossa equipe.
            </p>
            <p class="text-lg leading-relaxed text-slate-300">
                Nosso sistema utiliza a integração com o <span class="font-semibold text-white">Google Drive</span> para armazenar, organizar e fornecer acesso seguro e ágil aos arquivos e anexos de todos os nossos projetos diretamente pela plataforma.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('login') }}" class="rounded-lg bg-brand-500 px-8 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-400 transition-colors w-full sm:w-auto text-center">
                Acessar o Sistema
            </a>
            <a href="mailto:contato@consultoriastr.com.br" class="text-sm font-semibold leading-6 text-slate-400 hover:text-slate-200 transition-colors">
                Suporte Técnico <span aria-hidden="true">→</span>
            </a>
        </div>
        
        <div class="mt-16 pt-8 border-t border-ink-800 flex flex-wrap justify-center gap-6 text-sm text-slate-400">
            <a href="{{ route('privacy') }}" class="hover:text-slate-200 transition-colors">Política de Privacidade</a>
            <a href="{{ route('terms') }}" class="hover:text-slate-200 transition-colors">Termos de Serviço</a>
        </div>
    </div>
</body>
</html>
