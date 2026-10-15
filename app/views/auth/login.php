<?php
/** @var string|null $error */
use App\Core\PublicSite;
use App\Core\Security;

$productName = PublicSite::productName();
$csrf = Security::csrfToken();

$benefits = [
    ['title' => 'Gestão Estratégica', 'text' => 'Acompanhe indicadores, metas e prioridades com visão executiva.'],
    ['title' => 'Desenvolvimento de Pessoas', 'text' => 'Avaliações de desempenho, PDI e feedbacks estruturados em um só fluxo.'],
    ['title' => 'Cronogramas Inteligentes', 'text' => 'Gerencie ações, entregas e compromissos sem perder contexto.'],
    ['title' => 'Planos de Ação', 'text' => 'Centralize melhorias, responsáveis, prazos e execução.'],
    ['title' => 'Auditorias e Governança', 'text' => 'Fortaleça conformidade, controle e melhoria contínua.'],
    ['title' => 'Indicadores Centralizados', 'text' => 'Acompanhe metas e resultados em um único painel para decisões mais seguras.'],
];

$features = [
    ['title' => 'Dashboard Gerencial', 'text' => 'Painéis executivos para leitura rápida dos principais resultados.'],
    ['title' => 'Pilar de Pessoas', 'text' => 'Avaliações de desempenho, PDI, GAPs e feedbacks estruturados no modelo SBI.'],
    ['title' => 'Cronogramas', 'text' => 'Organize eventos, ocorrências e marcos de execução com clareza.'],
    ['title' => 'Planos de Ação', 'text' => 'Transforme diagnóstico em execução com acompanhamento contínuo.'],
    ['title' => 'Indicadores', 'text' => 'Monitore metas, histórico e evolução de performance.'],
    ['title' => 'Auditorias', 'text' => 'Acompanhe conformidade, evidências e oportunidades de melhoria.'],
    ['title' => 'Treinamentos', 'text' => 'Planeje, agende e acompanhe capacitações, com emissão de certificado.'],
    ['title' => 'Processos', 'text' => 'Centralize tratamentos, ações corretivas e ciclos de melhoria contínua.'],
];
?>
<a href="#conteudo" class="skip-link">Pular para o conteúdo</a>

<header class="site-header" data-site-header>
    <div class="container site-header__inner">
        <a href="index.php?route=auth/login" class="brand" aria-label="<?= htmlspecialchars(PublicSite::ORG_NAME) ?>">
            <img src="assets/img/logobco.png" alt="<?= htmlspecialchars(PublicSite::ORG_NAME) ?>" class="brand__mark" width="140" height="34" loading="eager" />
            <span class="brand__text">
                <span class="brand__org"><?= htmlspecialchars(PublicSite::ORG_NAME) ?></span>
                <span class="brand__product"><?= htmlspecialchars($productName) ?></span>
            </span>
        </a>

        <nav class="site-nav" aria-label="Navegação principal">
            <a href="#beneficios">Benefícios</a>
            <a href="#funcionalidades">Funcionalidades</a>
            <a href="#institucional">Institucional</a>
        </nav>

        <div class="site-header__actions">
            <button type="button" class="btn btn--primary" data-login-open>Entrar no Sistema</button>
            <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="mobileNav" aria-label="Abrir menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
    <nav id="mobileNav" class="mobile-nav" data-mobile-nav aria-label="Navegação móvel" hidden>
        <a href="#beneficios">Benefícios</a>
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="#institucional">Institucional</a>
        <button type="button" class="btn btn--primary btn--block" data-login-open>Entrar no Sistema</button>
    </nav>
</header>

<main id="conteudo">
    <section class="hero">
        <div class="container hero__grid">
            <div class="hero__content">
                <p class="eyebrow">
                    <span class="eyebrow__dot" aria-hidden="true"></span>
                    <?= htmlspecialchars($productName) ?> — um produto do <?= htmlspecialchars(PublicSite::ORG_NAME) ?>
                </p>
                <h1>Transformando organizações através da gestão, governança e desenvolvimento contínuo.</h1>
                <p class="hero__lead">
                    A plataforma do <?= htmlspecialchars(PublicSite::ORG_NAME) ?> para acompanhar indicadores, avaliações de desempenho, cronogramas, planos de ação e auditorias em um único ambiente.
                </p>
                <div class="hero__actions">
                    <button type="button" class="btn btn--primary btn--lg" data-login-open>Entrar no Sistema</button>
                    <a href="#funcionalidades" class="btn btn--ghost btn--lg">Conhecer a Plataforma</a>
                </div>
                <dl class="hero__pillars">
                    <div>
                        <dt>Gestão</dt>
                        <dd>Indicadores, planos e cronogramas conectados.</dd>
                    </div>
                    <div>
                        <dt>Governança</dt>
                        <dd>Rastreabilidade e visão executiva para decisões seguras.</dd>
                    </div>
                    <div>
                        <dt>Evolução</dt>
                        <dd>Acompanhamento contínuo da transformação organizacional.</dd>
                    </div>
                </dl>
            </div>

            <div class="hero__visual" aria-hidden="true">
                <div class="hero-panel">
                    <div class="hero-panel__head">
                        <span class="hero-panel__kicker">Ambiente institucional</span>
                        <img src="assets/img/logobco.png" alt="" class="hero-panel__logo" width="74" height="18" loading="lazy" />
                    </div>
                    <ul class="hero-panel__list">
                        <li>Dashboard</li>
                        <li>Pilar de Pessoas</li>
                        <li>Auditorias</li>
                        <li>Planos de Ação</li>
                        <li>Indicadores</li>
                        <li>Cronogramas</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="beneficios" class="section">
        <div class="container">
            <header class="section__head">
                <p class="eyebrow eyebrow--center">Benefícios</p>
                <h2>Como o <?= htmlspecialchars(PublicSite::ORG_NAME) ?> impulsiona resultados</h2>
                <p class="section__lead">Uma experiência projetada para apoiar liderança, execução, conformidade e crescimento organizacional com clareza operacional.</p>
            </header>
            <div class="card-grid card-grid--3">
                <?php foreach ($benefits as $item): ?>
                    <article class="card">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="funcionalidades" class="section section--alt">
        <div class="container">
            <header class="section__head section__head--split">
                <div>
                    <p class="eyebrow">Funcionalidades</p>
                    <h2>Um ecossistema completo para a gestão organizacional</h2>
                    <p class="section__lead">Da visão executiva ao acompanhamento operacional, cada módulo foi pensado para apoiar decisões, governança e evolução contínua.</p>
                </div>
            </header>
            <div class="card-grid card-grid--4">
                <?php foreach ($features as $feature): ?>
                    <article class="card card--compact">
                        <h3><?= htmlspecialchars($feature['title']) ?></h3>
                        <p><?= htmlspecialchars($feature['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="institucional" class="section">
        <div class="container about-grid">
            <div class="about-card">
                <p class="eyebrow eyebrow--on-dark">Sobre o <?= htmlspecialchars(PublicSite::ORG_NAME) ?></p>
                <h2>Uma base institucional elegante, pronta para evolução contínua.</h2>
                <p>O <?= htmlspecialchars(PublicSite::ORG_NAME) ?> desenvolve o <?= htmlspecialchars($productName) ?> para apoiar organizações em sua jornada de gestão, governança e desenvolvimento contínuo.</p>
                <div class="about-card__pillars">
                    <div>
                        <span>Missão</span>
                        <p>Apoiar organizações em sua jornada de gestão, governança e desenvolvimento contínuo.</p>
                    </div>
                    <div>
                        <span>Visão</span>
                        <p>Ser referência em transformação empresarial orientada por dados, execução e maturidade organizacional.</p>
                    </div>
                </div>
            </div>
            <div class="about-side">
                <article class="card">
                    <h3>Valores</h3>
                    <ul class="list">
                        <li>Ética, responsabilidade e visão de longo prazo.</li>
                        <li>Clareza na gestão e compromisso com resultado.</li>
                        <li>Desenvolvimento humano e organizacional contínuo.</li>
                        <li>Inovação aplicada à rotina corporativa.</li>
                    </ul>
                </article>
                <article class="card">
                    <h3>Diferenciais</h3>
                    <ul class="list">
                        <li>Indicadores, auditorias, avaliações e planos de ação integrados.</li>
                        <li>Visão executiva com base em acompanhamento estruturado.</li>
                        <li>Arquitetura preparada para operação multiempresa.</li>
                        <li>Experiência responsiva, para desktop e mobile.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--cta">
        <div class="container cta-panel">
            <div>
                <p class="eyebrow eyebrow--on-dark">Pronto para começar</p>
                <h2>Conheça a experiência de gestão do <?= htmlspecialchars(PublicSite::ORG_NAME) ?></h2>
                <p>Acesse a plataforma para acompanhar indicadores, avaliações, cronogramas, planos de ação e evolução organizacional.</p>
            </div>
            <div class="cta-panel__actions">
                <a href="#funcionalidades" class="btn btn--outline-light">Ver Funcionalidades</a>
                <button type="button" class="btn btn--primary btn--lg" data-login-open>Entrar no Sistema</button>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <img src="assets/img/logobco.png" alt="<?= htmlspecialchars(PublicSite::ORG_NAME) ?>" class="footer-brand__logo" width="120" height="29" loading="lazy" />
            <p><?= htmlspecialchars($productName) ?> é a plataforma do <?= htmlspecialchars(PublicSite::ORG_NAME) ?> para gestão, governança e desenvolvimento organizacional.</p>
        </div>
        <div class="footer-col">
            <span class="footer-col__title">Institucional</span>
            <a href="#beneficios">Benefícios</a>
            <a href="#funcionalidades">Funcionalidades</a>
            <a href="#institucional">Sobre o <?= htmlspecialchars(PublicSite::ORG_NAME) ?></a>
        </div>
        <div class="footer-col">
            <span class="footer-col__title">Legal</span>
            <a href="#politica-privacidade">Política de Privacidade</a>
            <a href="#termos-uso">Termos de Uso</a>
            <button type="button" class="link-button" data-login-open>Área do Cliente</button>
        </div>
    </div>

    <div class="container">
        <div id="politica-privacidade" class="legal-note">
            <strong>Política de Privacidade:</strong> área preparada para publicação futura das diretrizes de tratamento de dados e privacidade institucional.
        </div>
        <div id="termos-uso" class="legal-note">
            <strong>Termos de Uso:</strong> área preparada para publicação futura das regras contratuais e condições de uso da plataforma.
        </div>
    </div>

    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(PublicSite::ORG_NAME) ?>. Todos os direitos reservados.</p>
        <p class="footer-credit">
            <?= htmlspecialchars($productName) ?> — um produto do <?= htmlspecialchars(PublicSite::ORG_NAME) ?>.
            Desenvolvido por <a href="<?= htmlspecialchars(\App\Core\PublicSite::DEVELOPER_URL) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(\App\Core\PublicSite::DEVELOPER_SHORT_NAME) ?> — Automações e Sistemas</a>
        </p>
    </div>
</footer>

<div class="login-modal" data-login-modal data-has-error="<?= !empty($error) ? '1' : '0' ?>" aria-hidden="true">
    <div class="login-modal__backdrop" data-login-backdrop></div>
    <div class="login-modal__panel" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle" tabindex="-1" data-login-panel>
        <div class="login-modal__head">
            <div>
                <p class="eyebrow">Acesso</p>
                <h2 id="loginModalTitle">Entrar no Sistema</h2>
            </div>
            <button type="button" class="icon-close" aria-label="Fechar" data-login-close>&times;</button>
        </div>

        <div class="login-modal__body">
            <?php if (!empty($error)): ?>
                <div role="alert" class="form-alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php?route=auth/doLogin" class="login-form" data-login-form>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <div class="form-field">
                    <label for="login_email">E-mail</label>
                    <input id="login_email" name="email" type="email" autocomplete="email" required />
                </div>
                <div class="form-field">
                    <label for="login_senha">Senha</label>
                    <input id="login_senha" name="senha" type="password" autocomplete="current-password" required />
                </div>
                <div class="login-form__row">
                    <label class="checkbox">
                        <input type="checkbox" name="remember_access" value="1" />
                        <span>Lembrar acesso</span>
                    </label>
                    <button type="button" class="link-button" data-recover-toggle>Recuperar senha</button>
                </div>
                <p class="login-form__tip" data-recover-tip hidden>
                    Para recuperar seu acesso, entre em contato com o administrador responsável pelo ambiente da sua organização.
                </p>
                <button class="btn btn--primary btn--block btn--lg" type="submit">Entrar</button>
            </form>
        </div>
    </div>
</div>
