<?php /** @var string|null $error */ ?>
<?php use App\Core\Security; ?>
<?php $brandName = \App\Core\AppBrand::displayName(); ?>
<?php $brandTagline = \App\Core\AppBrand::TAGLINE; ?>
<?php
$benefits = [
    ['icon' => 'target', 'title' => 'Gestão Estratégica', 'text' => 'Acompanhe indicadores, metas e prioridades com visão executiva.'],
    ['icon' => 'check-square', 'title' => 'Avaliações Organizacionais', 'text' => 'Monitore maturidade, evolução e desempenho com clareza.'],
    ['icon' => 'calendar', 'title' => 'Cronogramas Inteligentes', 'text' => 'Gerencie ações, entregas e compromissos sem perder contexto.'],
    ['icon' => 'activity', 'title' => 'Planos de Ação', 'text' => 'Centralize melhorias, responsáveis, prazos e execução.'],
    ['icon' => 'shield', 'title' => 'Auditorias e Governança', 'text' => 'Fortaleça conformidade, controle e melhoria contínua.'],
    ['icon' => 'bar-chart-2', 'title' => 'Indicadores em Tempo Real', 'text' => 'Visualize resultados rapidamente para decisões mais seguras.'],
];

$features = [
    ['icon' => 'home', 'title' => 'Dashboard Gerencial', 'text' => 'Painéis executivos para leitura rápida dos principais resultados.'],
    ['icon' => 'clipboard', 'title' => 'Avaliações', 'text' => 'Estruture diagnósticos, análises e acompanhamentos em um único fluxo.'],
    ['icon' => 'calendar', 'title' => 'Cronogramas', 'text' => 'Organize eventos, ocorrências e marcos de execução com clareza.'],
    ['icon' => 'activity', 'title' => 'Planos de Ação', 'text' => 'Transforme diagnóstico em execução com acompanhamento contínuo.'],
    ['icon' => 'bar-chart-2', 'title' => 'Indicadores', 'text' => 'Monitore metas, histórico e evolução de performance.'],
    ['icon' => 'shield', 'title' => 'Auditorias', 'text' => 'Acompanhe conformidade, evidências e oportunidades de melhoria.'],
    ['icon' => 'users', 'title' => 'Gestão de Oficinas', 'text' => 'Apoie desenvolvimento, capacitação e engajamento das equipes.'],
    ['icon' => 'layers', 'title' => 'Gestão de Tratamentos', 'text' => 'Controle ações corretivas, preventivas e ciclos de melhoria.'],
];

$results = [
    ['value' => '120+', 'label' => 'Empresas atendidas'],
    ['value' => '1.500+', 'label' => 'Avaliações realizadas'],
    ['value' => '3.200+', 'label' => 'Planos acompanhados'],
    ['value' => '8.000+', 'label' => 'Indicadores monitorados'],
];
?>
<style>
    .landing-bg {
        background:
            radial-gradient(circle at top left, rgba(247,127,137,.22), transparent 28%),
            radial-gradient(circle at top right, rgba(200,49,4,.18), transparent 30%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 48%, #f8fafc 100%);
    }
    .landing-grid {
        background-image:
            linear-gradient(rgba(16,32,64,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(16,32,64,.05) 1px, transparent 1px);
        background-size: 36px 36px;
    }
    .glass-card {
        background: rgba(255,255,255,.72);
        backdrop-filter: blur(18px);
        border: 1px solid rgba(255,255,255,.5);
        box-shadow: 0 24px 80px rgba(16,32,64,.12);
    }
    .hero-visual {
        background:
            linear-gradient(155deg, rgba(16,32,64,.92), rgba(16,32,64,.72)),
            radial-gradient(circle at top, rgba(247,127,137,.35), transparent 30%),
            radial-gradient(circle at bottom right, rgba(200,49,4,.35), transparent 32%);
    }
    .metric-chip {
        background: linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,.07));
        border: 1px solid rgba(255,255,255,.18);
    }
    .section-card {
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .section-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 48px rgba(16,32,64,.12);
        border-color: rgba(200,49,4,.18);
    }
    .login-modal-shell {
        transition: opacity .2s ease, visibility .2s ease;
    }
    .login-modal-panel {
        transition: transform .24s ease, opacity .24s ease;
    }
    .login-modal-shell.is-open {
        opacity: 1;
        visibility: visible;
    }
    .login-modal-shell.is-open .login-modal-panel {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
</style>

<div class="min-h-screen landing-bg landing-grid">
    <header class="sticky top-0 z-20 border-b border-white/60 bg-white/80 backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4 py-4">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="assets/img/logobco.png" alt="Instituto Doná" class="h-11 w-auto rounded-lg bg-brand-brown p-2 shadow" loading="eager" />
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Instituto Doná</div>
                        <div class="truncate text-sm text-gray-600"><?= htmlspecialchars($brandName) ?><?php if (!empty($brandTagline) && $brandTagline !== $brandName): ?> · <?= htmlspecialchars($brandTagline) ?><?php endif; ?></div>
                    </div>
                </div>
                <nav class="hidden items-center gap-6 text-sm text-gray-600 lg:flex">
                    <a href="#beneficios" class="hover:text-brand-brown">Benefícios</a>
                    <a href="#funcionalidades" class="hover:text-brand-brown">Funcionalidades</a>
                    <a href="#sobre" class="hover:text-brand-brown">Institucional</a>
                    <a href="#resultados" class="hover:text-brand-brown">Resultados</a>
                </nav>
                <button type="button"
                        class="inline-flex items-center justify-center rounded-xl bg-brand-brown px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-brown/20 transition hover:bg-[#0b1630] focus:outline-none focus:ring-2 focus:ring-brand-red focus:ring-offset-2"
                        data-login-open>
                    Entrar no Sistema
                </button>
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:py-20 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:py-24">
                <div class="flex flex-col justify-center">
                    <div class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-brand-red/20 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-brand-brown shadow-sm">
                        <span class="inline-block h-2 w-2 rounded-full bg-brand-red"></span>
                        Plataforma de gestão e desenvolvimento organizacional
                    </div>
                    <h1 class="max-w-3xl text-4xl font-black leading-tight text-brand-brown sm:text-5xl lg:text-6xl">
                        Transformando organizações através da gestão, governança e desenvolvimento contínuo.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-gray-600 sm:text-xl">
                        Uma plataforma completa para acompanhar indicadores, avaliações, cronogramas, planos de ação e evolução organizacional.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <button type="button"
                                class="inline-flex items-center justify-center rounded-2xl bg-brand-red px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-red/20 transition hover:bg-[#a52803] focus:outline-none focus:ring-2 focus:ring-brand-red focus:ring-offset-2"
                                data-login-open>
                            Entrar no Sistema
                        </button>
                        <a href="#demonstracao"
                           class="inline-flex items-center justify-center rounded-2xl border border-brand-brown/15 bg-white px-6 py-3 text-sm font-semibold text-brand-brown shadow-sm transition hover:border-brand-brown/30 hover:bg-brand-brown hover:text-white">
                            Solicitar Demonstração
                        </a>
                    </div>
                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="glass-card rounded-2xl p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-red">Gestão</div>
                            <div class="mt-2 text-sm text-gray-600">Indicadores, planos e cronogramas conectados em um só ambiente.</div>
                        </div>
                        <div class="glass-card rounded-2xl p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-red">Governança</div>
                            <div class="mt-2 text-sm text-gray-600">Rastreabilidade, responsabilidade e visão executiva para decisões seguras.</div>
                        </div>
                        <div class="glass-card rounded-2xl p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-red">Evolução</div>
                            <div class="mt-2 text-sm text-gray-600">Acompanhamento contínuo da transformação empresarial e organizacional.</div>
                        </div>
                    </div>
                </div>

                <div class="relative flex items-center justify-center">
                    <div class="hero-visual relative w-full overflow-hidden rounded-[2rem] p-6 text-white shadow-[0_30px_90px_rgba(16,32,64,.28)] sm:p-8">
                        <div class="absolute -left-14 top-10 h-32 w-32 rounded-full bg-brand-pink/30 blur-3xl"></div>
                        <div class="absolute -bottom-10 right-0 h-40 w-40 rounded-full bg-brand-red/30 blur-3xl"></div>
                        <div class="relative z-10 space-y-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.24em] text-white/70">Instituto Doná</div>
                                    <div class="mt-2 text-2xl font-bold">Ambiente corporativo premium</div>
                                </div>
                                <img src="assets/img/logobco.png" alt="Logo Instituto Doná" class="h-16 w-auto rounded-2xl bg-white/10 p-3 backdrop-blur" loading="lazy" />
                            </div>

                            <div class="grid gap-4">
                                <div class="metric-chip rounded-3xl p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="text-xs uppercase tracking-[0.22em] text-white/60">Visão Executiva</div>
                                            <div class="mt-2 text-xl font-semibold">Resultados, governança e execução alinhados.</div>
                                        </div>
                                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">Tempo real</span>
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="metric-chip rounded-3xl p-5">
                                        <div class="text-xs uppercase tracking-[0.22em] text-white/60">Indicadores</div>
                                        <div class="mt-2 text-3xl font-black">+98%</div>
                                        <div class="mt-1 text-sm text-white/70">visibilidade sobre metas e acompanhamento.</div>
                                    </div>
                                    <div class="metric-chip rounded-3xl p-5">
                                        <div class="text-xs uppercase tracking-[0.22em] text-white/60">Governança</div>
                                        <div class="mt-2 text-3xl font-black">360°</div>
                                        <div class="mt-1 text-sm text-white/70">leitura integrada de auditorias, planos e cronogramas.</div>
                                    </div>
                                </div>
                                <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="text-xs uppercase tracking-[0.22em] text-white/60">Jornada de Transformação</div>
                                            <div class="mt-2 text-sm text-white/80">Avaliação, plano, cronograma, execução e melhoria contínua em uma única plataforma.</div>
                                        </div>
                                        <div class="grid gap-2 text-right text-sm font-semibold">
                                            <span>Dashboard</span>
                                            <span>Auditorias</span>
                                            <span>Planos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="beneficios" class="py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">Benefícios</div>
                    <h2 class="mt-4 text-3xl font-black text-brand-brown sm:text-4xl">Como o Instituto Doná impulsiona resultados</h2>
                    <p class="mt-4 text-lg text-gray-600">Uma experiência projetada para apoiar liderança, execução, conformidade e crescimento organizacional com clareza operacional.</p>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($benefits as $item): ?>
                        <article class="section-card rounded-3xl border border-white/70 bg-white p-6 shadow-[0_18px_45px_rgba(16,32,64,.08)]">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-brown text-white shadow-lg shadow-brand-brown/20">
                                <span data-feather="<?= htmlspecialchars($item['icon']) ?>"></span>
                            </div>
                            <h3 class="mt-5 text-xl font-bold text-brand-brown"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="mt-3 text-sm leading-7 text-gray-600"><?= htmlspecialchars($item['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="funcionalidades" class="border-y border-white/70 bg-white/70 py-16 backdrop-blur-sm sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">Funcionalidades</div>
                        <h2 class="mt-3 text-3xl font-black text-brand-brown sm:text-4xl">Um ecossistema completo para a gestão organizacional</h2>
                        <p class="mt-4 text-lg text-gray-600">Da visão executiva ao acompanhamento operacional, cada módulo foi pensado para apoiar decisões, governança e evolução contínua.</p>
                    </div>
                    <div class="text-sm text-gray-500">Estrutura modular e preparada para expansão futura.</div>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <?php foreach ($features as $feature): ?>
                        <article class="section-card rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-red text-white shadow-lg shadow-brand-red/20">
                                <span data-feather="<?= htmlspecialchars($feature['icon']) ?>"></span>
                            </div>
                            <h3 class="mt-5 text-lg font-bold text-brand-brown"><?= htmlspecialchars($feature['title']) ?></h3>
                            <p class="mt-2 text-sm leading-7 text-gray-600"><?= htmlspecialchars($feature['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="sobre" class="py-16 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.02fr_.98fr] lg:px-8">
                <div class="rounded-[2rem] bg-brand-brown p-8 text-white shadow-[0_24px_80px_rgba(16,32,64,.2)]">
                    <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-pink">Sobre o Instituto Doná</div>
                    <h2 class="mt-4 text-3xl font-black sm:text-4xl">Uma base institucional elegante, pronta para evolução futura.</h2>
                    <p class="mt-5 text-base leading-8 text-white/80">
                        Esta área foi estruturada para receber e expandir facilmente conteúdos institucionais do Instituto Doná, reforçando posicionamento, proposta de valor e diferenciais da organização.
                    </p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl bg-white/10 p-5 backdrop-blur">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Missão</div>
                            <p class="mt-3 text-sm leading-7 text-white/85">Apoiar organizações em sua jornada de gestão, governança e desenvolvimento contínuo.</p>
                        </div>
                        <div class="rounded-3xl bg-white/10 p-5 backdrop-blur">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Visão</div>
                            <p class="mt-3 text-sm leading-7 text-white/85">Ser referência em transformação empresarial orientada por dados, execução e maturidade organizacional.</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-6">
                    <article class="rounded-3xl border border-gray-200 bg-white p-7 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">Valores</div>
                        <ul class="mt-4 space-y-3 text-sm leading-7 text-gray-600">
                            <li>Ética, responsabilidade e visão de longo prazo.</li>
                            <li>Clareza na gestão e compromisso com resultado.</li>
                            <li>Desenvolvimento humano e organizacional contínuo.</li>
                            <li>Inovação aplicada à rotina corporativa.</li>
                        </ul>
                    </article>
                    <article class="rounded-3xl border border-gray-200 bg-white p-7 shadow-sm">
                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">Diferenciais</div>
                        <ul class="mt-4 space-y-3 text-sm leading-7 text-gray-600">
                            <li>Integração entre indicadores, auditorias, avaliações e planos de ação.</li>
                            <li>Visão executiva com base em acompanhamento estruturado.</li>
                            <li>Arquitetura preparada para crescimento, governança e operação multiempresa.</li>
                            <li>Experiência responsiva e aderente a contextos corporativos modernos.</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section id="resultados" class="py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-[2rem] border border-white/80 bg-white/80 p-8 shadow-[0_20px_60px_rgba(16,32,64,.08)] backdrop-blur">
                    <div class="mx-auto max-w-3xl text-center">
                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">Resultados</div>
                        <h2 class="mt-4 text-3xl font-black text-brand-brown sm:text-4xl">Métricas configuráveis para reforçar prova de valor</h2>
                        <p class="mt-4 text-lg text-gray-600">Os números abaixo foram estruturados para atualização futura com dados institucionais reais, sem necessidade de reformular o layout.</p>
                    </div>
                    <div class="mt-10 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        <?php foreach ($results as $result): ?>
                            <article class="rounded-3xl border border-brand-brown/10 bg-gradient-to-br from-white to-slate-50 p-6 text-center shadow-sm">
                                <div class="text-4xl font-black text-brand-brown"><?= htmlspecialchars($result['value']) ?></div>
                                <div class="mt-3 text-sm font-medium text-gray-600"><?= htmlspecialchars($result['label']) ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section id="demonstracao" class="pb-16 pt-4 sm:pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-[2rem] bg-gradient-to-r from-brand-brown via-[#1a2d54] to-brand-brown p-8 text-white shadow-[0_24px_80px_rgba(16,32,64,.22)] sm:p-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-pink">Demonstração</div>
                            <h2 class="mt-3 text-3xl font-black sm:text-4xl">Conheça a experiência de gestão do Instituto Doná</h2>
                            <p class="mt-4 text-base leading-8 text-white/80">Apresente a plataforma, seus fluxos, indicadores e potencial de transformação organizacional em um ambiente institucional premium.</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a href="#sobre" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                                Ver Institucional
                            </a>
                            <button type="button" class="inline-flex items-center justify-center rounded-2xl bg-brand-red px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-red/20 transition hover:bg-[#a52803]" data-login-open>
                                Entrar no Sistema
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/70 bg-white/85 backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1.2fr_.8fr_.8fr]">
                <div>
                    <div class="flex items-center gap-3">
                        <img src="assets/img/logobco.png" alt="Instituto Doná" class="h-12 w-auto rounded-xl bg-brand-brown p-2 shadow" loading="lazy" />
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-red">Instituto Doná</div>
                            <div class="text-sm text-gray-600"><?= htmlspecialchars($brandName) ?></div>
                        </div>
                    </div>
                    <p class="mt-4 max-w-md text-sm leading-7 text-gray-600">Landing page institucional desenvolvida para reforçar credibilidade, inovação, gestão, governança e desenvolvimento organizacional antes do acesso ao sistema.</p>
                </div>
                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-brown">Links institucionais</div>
                    <div class="mt-4 flex flex-col gap-3 text-sm text-gray-600">
                        <a href="#beneficios" class="hover:text-brand-brown">Benefícios</a>
                        <a href="#funcionalidades" class="hover:text-brand-brown">Funcionalidades</a>
                        <a href="#sobre" class="hover:text-brand-brown">Sobre o Instituto Doná</a>
                        <a href="#resultados" class="hover:text-brand-brown">Resultados</a>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-brown">Legal</div>
                    <div class="mt-4 flex flex-col gap-3 text-sm text-gray-600">
                        <a href="#politica-privacidade" class="hover:text-brand-brown">Política de Privacidade</a>
                        <a href="#termos-uso" class="hover:text-brand-brown">Termos de Uso</a>
                        <button type="button" class="text-left hover:text-brand-brown" data-login-open>Área do Cliente</button>
                    </div>
                </div>
            </div>
            <div id="politica-privacidade" class="mt-8 rounded-3xl border border-gray-200 bg-white p-5 text-sm leading-7 text-gray-600 shadow-sm">
                <span class="font-semibold text-brand-brown">Política de Privacidade:</span>
                área preparada para publicação futura das diretrizes de tratamento de dados e privacidade institucional.
            </div>
            <div id="termos-uso" class="mt-4 rounded-3xl border border-gray-200 bg-white p-5 text-sm leading-7 text-gray-600 shadow-sm">
                <span class="font-semibold text-brand-brown">Termos de Uso:</span>
                área preparada para publicação futura das regras contratuais e condições de uso da plataforma.
            </div>
            <div class="mt-8 flex flex-col gap-3 border-t border-gray-200 pt-6 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                <div>&copy; <?= date('Y') ?> Instituto Doná. Todos os direitos reservados.</div>
                <div><?= htmlspecialchars($brandName) ?><?php if (!empty($brandTagline) && $brandTagline !== $brandName): ?> · <?= htmlspecialchars($brandTagline) ?><?php endif; ?></div>
            </div>
        </div>
    </footer>
</div>

<div class="login-modal-shell invisible fixed inset-0 z-50 bg-slate-950/55 p-0 opacity-0 sm:p-6" data-login-modal aria-hidden="true">
    <div class="absolute inset-0" data-login-backdrop></div>
    <div class="relative flex min-h-screen items-center justify-center sm:min-h-0">
        <div class="login-modal-panel relative flex min-h-screen w-full translate-y-4 flex-col bg-white opacity-0 sm:min-h-0 sm:max-w-lg sm:rounded-[2rem] sm:shadow-[0_30px_90px_rgba(16,32,64,.24)]"
             role="dialog"
             aria-modal="true"
             aria-labelledby="loginModalTitle"
             tabindex="-1"
             data-login-panel>
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-7">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-red">Acesso</div>
                    <h2 id="loginModalTitle" class="mt-1 text-2xl font-bold text-brand-brown">Entrar no Sistema</h2>
                </div>
                <button type="button" class="icon-btn icon-btn--lg icon-btn--muted" aria-label="Fechar modal de login" data-login-close>
                    <span data-feather="x"></span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-6 sm:px-7 sm:py-7">
                <div class="mb-6 rounded-3xl bg-gradient-to-r from-brand-brown to-[#1a2d54] p-5 text-white">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Instituto Doná</div>
                    <p class="mt-3 text-sm leading-7 text-white/85">Acesse a plataforma para acompanhar indicadores, avaliações, cronogramas, planos de ação e evolução organizacional.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div role="alert" class="mb-4 rounded-2xl border border-brand-red/20 bg-red-50 px-4 py-3 text-sm text-brand-red"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="index.php?route=auth/doLogin" class="space-y-5" data-login-form>
                    <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-brand-brown" for="login_email">E-mail</label>
                        <input id="login_email"
                               class="w-full rounded-2xl border border-gray-200 bg-slate-50 px-4 py-3 text-sm text-brand-brown outline-none transition focus:border-brand-red focus:bg-white focus:ring-2 focus:ring-brand-red/15"
                               name="email"
                               type="email"
                               autocomplete="email"
                               required />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-brand-brown" for="login_senha">Senha</label>
                        <input id="login_senha"
                               class="w-full rounded-2xl border border-gray-200 bg-slate-50 px-4 py-3 text-sm text-brand-brown outline-none transition focus:border-brand-red focus:bg-white focus:ring-2 focus:ring-brand-red/15"
                               name="senha"
                               type="password"
                               autocomplete="current-password"
                               required />
                    </div>

                    <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <label class="inline-flex items-center gap-3 text-gray-600">
                            <input type="checkbox" name="remember_access" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-red focus:ring-brand-red" />
                            <span>Lembrar acesso</span>
                        </label>
                        <button type="button" class="text-left font-medium text-brand-brown hover:text-brand-red" data-recover-toggle>
                            Recuperar senha
                        </button>
                    </div>

                    <div class="hidden rounded-2xl border border-brand-brown/10 bg-slate-50 px-4 py-3 text-sm leading-7 text-gray-600" data-recover-tip>
                        Para recuperar seu acesso, entre em contato com o administrador responsável pelo ambiente ou com o canal institucional definido pela sua organização.
                    </div>

                    <button class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-red px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-red/20 transition hover:bg-[#a52803] focus:outline-none focus:ring-2 focus:ring-brand-red focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            type="submit">
                        Entrar
                    </button>
                </form>

                <p class="mt-5 text-xs leading-6 text-gray-500">
                    O fluxo de autenticação permanece o mesmo do sistema atual, incluindo validação de sessão, CSRF, permissões e redirecionamento pós-login.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.querySelector('[data-login-modal]');
        const panel = document.querySelector('[data-login-panel]');
        const backdrop = document.querySelector('[data-login-backdrop]');
        const openButtons = document.querySelectorAll('[data-login-open]');
        const closeButtons = document.querySelectorAll('[data-login-close]');
        const recoverToggle = document.querySelector('[data-recover-toggle]');
        const recoverTip = document.querySelector('[data-recover-tip]');
        const loginForm = document.querySelector('[data-login-form]');
        const firstField = document.getElementById('login_email');
        const hasError = <?= !empty($error) ? 'true' : 'false' ?>;
        let lastFocusedElement = null;

        if (!modal || !panel) {
            try { feather.replace(); } catch (e) {}
            return;
        }

        const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

        const trapFocus = (event) => {
            if (event.key !== 'Tab') return;
            const focusable = Array.from(panel.querySelectorAll(focusableSelector)).filter((el) => !el.hasAttribute('disabled'));
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        const openModal = () => {
            lastFocusedElement = document.activeElement;
            modal.classList.add('is-open');
            modal.classList.remove('invisible');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            window.setTimeout(() => {
                if (firstField) firstField.focus();
                else panel.focus();
            }, 60);
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.classList.add('invisible');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', openModal);
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', (event) => {
            if (!modal.classList.contains('is-open')) return;
            if (event.key === 'Escape') {
                closeModal();
                return;
            }
            trapFocus(event);
        });

        if (recoverToggle && recoverTip) {
            recoverToggle.addEventListener('click', () => {
                recoverTip.classList.toggle('hidden');
            });
        }

        if (loginForm) {
            loginForm.addEventListener('submit', () => {
                const submit = loginForm.querySelector('button[type="submit"]');
                if (submit) {
                    submit.disabled = true;
                    submit.textContent = 'Entrando...';
                }
            });
        }

        if (hasError) {
            openModal();
        }

        try { feather.replace(); } catch (e) {}
    })();
</script>
