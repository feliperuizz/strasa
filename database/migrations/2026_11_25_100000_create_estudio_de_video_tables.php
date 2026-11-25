<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estúdio de vídeo: o Claude (CLI no servidor) decide a edição e o FFmpeg
     * executa. Cada cliente tem a própria identidade de vídeo e um caderno de
     * aprendizados que cresce com as revisões.
     *
     * Os arquivos de vídeo NÃO ficam no banco nem no bucket durante a edição:
     * ficam no disco local (config video.armazenamento). Aqui só metadados.
     */
    public function up(): void
    {
        // Biblioteca da agência: músicas, efeitos sonoros, fontes. client_id
        // preenchido = exclusivo daquele cliente (ex.: o jingle dele).
        Schema::create('video_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('tipo', 20); // musica | sfx | fonte
            $table->string('nome', 120);
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('tamanho')->default(0);
            $table->decimal('duracao', 9, 3)->nullable();
            $table->decimal('bpm', 6, 2)->nullable();
            // Tempos das batidas (s) — os cortes do "tour do espaço" caem nelas.
            $table->json('batidas')->nullable();
            $table->string('humor', 120)->nullable(); // "animada, urbana"
            // Obrigatório para música: de onde veio e qual licença. Sem isso não entra.
            $table->string('licenca', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'tipo']);
        });

        // Identidade de vídeo do cliente (um por cliente). A logo principal
        // continua em clients.logo_path; aqui entra a versão para vídeo (PNG
        // transparente), cores, fonte, assinatura e instruções para o Claude.
        Schema::create('video_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cor_primaria', 9)->default('#111827');
            $table->string('cor_destaque', 9)->default('#F59E0B');
            $table->string('cor_texto', 9)->default('#FFFFFF');
            $table->string('cor_fundo', 9)->default('#0B0B0F');
            $table->string('fonte_titulo', 60)->default('Montserrat');
            $table->string('fonte_texto', 60)->default('Montserrat');
            // Fonte própria da marca (TTF/OTF enviado). Tem prioridade.
            $table->string('fonte_path')->nullable();
            $table->string('fonte_nome', 80)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_posicao', 20)->default('topo_direita');
            $table->unsignedTinyInteger('logo_tamanho')->default(16); // % da largura
            $table->unsignedTinyInteger('logo_opacidade')->default(90);
            $table->string('legenda_estilo', 20)->default('karaoke');
            $table->string('legenda_posicao', 10)->default('baixo');
            $table->boolean('legenda_caixa_alta')->default(true);
            $table->string('cor_grade', 20)->default('natural');
            $table->string('cta_texto', 160)->nullable();
            $table->string('instagram', 80)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('site', 120)->nullable();
            $table->string('endereco', 160)->nullable();
            $table->string('vibe', 160)->nullable();
            $table->text('instrucoes')->nullable();
            $table->foreignId('musica_padrao_id')->nullable()->constrained('video_assets')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('video_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 160);
            $table->string('estilo', 30);
            $table->string('formato', 10)->default('9:16');
            $table->unsignedSmallInteger('duracao_alvo')->nullable(); // s
            $table->text('briefing')->nullable();
            $table->json('opcoes')->nullable();
            $table->foreignId('musica_id')->nullable()->constrained('video_assets')->nullOnDelete();

            $table->string('status', 30)->default('rascunho');
            $table->string('etapa', 30)->nullable();
            $table->unsignedTinyInteger('progresso')->default(0);
            $table->string('mensagem', 255)->nullable();
            $table->text('erro')->nullable();

            // O plano de edição (EDL) atual. Versões antigas ficam no disco.
            $table->longText('plano')->nullable();
            $table->unsignedInteger('plano_versao')->default(0);
            $table->unsignedInteger('previa_versao')->default(0);
            $table->unsignedInteger('final_versao')->default(0);
            $table->string('previa_path')->nullable();
            $table->string('final_path')->nullable();
            $table->string('capa_path')->nullable();
            $table->text('legenda_post')->nullable();
            $table->boolean('feito_por_claude')->default(false);

            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claude_sessao', 80)->nullable();
            $table->json('claude_uso')->nullable();

            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->timestamp('sinal_em')->nullable(); // batimento do worker
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamp('limpo_em')->nullable(); // brutos apagados pela retenção
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'created_at']);
        });

        Schema::create('video_job_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_job_id')->constrained()->cascadeOnDelete();
            $table->string('papel', 20)->default('bruto'); // bruto | antes | depois
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->string('nome_original');
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('tamanho')->default(0);
            $table->unsignedBigInteger('recebido')->default(0);
            $table->string('status', 20)->default('enviando'); // enviando | pronto | erro
            $table->string('tipo', 10)->nullable(); // video | imagem | audio
            $table->decimal('duracao', 10, 3)->nullable();
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('altura')->nullable();
            $table->decimal('fps', 7, 3)->nullable();
            $table->smallInteger('rotacao')->default(0);
            $table->boolean('hdr')->default(false);
            $table->boolean('tem_audio')->default(false);
            $table->json('sondagem')->nullable();
            $table->timestamps();

            $table->index(['video_job_id', 'ordem']);
        });

        // Linha do tempo da edição: o que o sistema fez, os pedidos de ajuste
        // e as respostas do Claude. É o "chat" da tela da edição.
        Schema::create('video_job_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 20); // sistema | pedido | claude | erro | versao
            $table->text('texto');
            $table->json('dados')->nullable();
            $table->timestamps();

            $table->index(['video_job_id', 'id']);
        });

        // Caderno de aprendizados do cliente ("zoom mais sutil", "nunca
        // mostrar a fachada antiga"). Só os aplicados entram no contexto do
        // Claude nas próximas edições.
        Schema::create('video_learnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_job_id')->nullable()->constrained()->nullOnDelete();
            $table->string('texto', 500);
            $table->string('etapa', 30)->nullable();
            $table->string('status', 20)->default('pendente'); // pendente | aplicado | descartado
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_learnings');
        Schema::dropIfExists('video_job_events');
        Schema::dropIfExists('video_job_files');
        Schema::dropIfExists('video_jobs');
        Schema::dropIfExists('video_profiles');
        Schema::dropIfExists('video_assets');
    }
};
