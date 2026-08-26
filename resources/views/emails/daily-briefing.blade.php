<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .header { background-color: #111111; padding: 30px 40px; text-align: center; }
        .header img { height: 40px; }
        .content { padding: 40px; }
        .greeting { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }
        .intro { font-size: 16px; line-height: 1.5; color: #475569; margin-bottom: 30px; }
        .task-list { list-style: none; padding: 0; margin: 0; }
        .task-item { padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px; background-color: #f8fafc; }
        .task-title { font-size: 16px; font-weight: 600; color: #0f172a; margin: 0 0 5px 0; }
        .task-meta { font-size: 14px; color: #64748b; margin: 0; display: flex; justify-content: space-between; }
        .task-meta strong { color: #3b82f6; }
        .footer { background-color: #f1f5f9; padding: 20px 40px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .button-wrapper { text-align: center; margin-top: 30px; }
        .button { display: inline-block; background-color: #6366f1; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('strasalogo.png') }}" alt="Strasa">
        </div>
        <div class="content">
            <h1 class="greeting">Bom dia, {{ explode(' ', $user->name)[0] }}! ☀️</h1>
            <p class="intro">Aqui está o seu briefing para hoje. Você tem <strong>{{ count($tasks) }} tarefas</strong> programadas para publicação ou conclusão.</p>
            
            <ul class="task-list">
                @foreach($tasks as $task)
                    <li class="task-item">
                        <h3 class="task-title">{{ $task->title }}</h3>
                        <p class="task-meta">
                            <span>
                                📁 {{ $task->project->name }}
                                @if($task->client)
                                    ({{ $task->client->name }})
                                @endif
                            </span>
                            @if($task->publish_time)
                                <strong>⏰ {{ \Carbon\Carbon::parse($task->publish_time)->format('H:i') }}</strong>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>

            <div class="button-wrapper">
                <a href="{{ url('/') }}" class="button">Acessar o Strasa</a>
            </div>
        </div>
        <div class="footer">
            <p>Você está recebendo este e-mail porque ativou o Briefing Diário na sua conta Strasa.<br>Para desativar, acesse as configurações do seu perfil.</p>
            <p>&copy; {{ date('Y') }} Strasa. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
