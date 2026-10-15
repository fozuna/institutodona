<?php
/**
 * Layout público (Sprint: redesign da landing page) - totalmente isolado do
 * layout administrativo (layouts/main.php). Nenhuma classe/elemento de
 * sidebar aqui: a causa do "vão à esquerda" era .app-main (sempre presente
 * em main.php) receber `margin-left: var(--sidebar-width)` do theme.css
 * mesmo quando o <aside> não era renderizado (usuário sem sessão). Este
 * layout não reaproveita theme.css/app.js nem o script CDN do Tailwind -
 * usa um CSS próprio, enxuto, sem dependências pesadas.
 *
 * @var string $content HTML já renderizado da view (via BaseController::renderPublic)
 * @var string $pageTitle
 */
use App\Core\PublicSite;

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(dirname($scriptName), '/\\');
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
$assetsUrl = $baseUrl . '/assets';
$publicCssPath = __DIR__ . '/../../../public_html/assets/css/public.css';
$publicJsPath = __DIR__ . '/../../../public_html/assets/js/public.js';
$publicCssVersion = is_file($publicCssPath) ? (string)filemtime($publicCssPath) : '1';
$publicJsVersion = is_file($publicJsPath) ? (string)filemtime($publicJsPath) : '1';

$metaTitle = isset($metaTitle) && $metaTitle !== '' ? $metaTitle : (PublicSite::productName() . ' — ' . PublicSite::ORG_NAME);
$metaDescription = isset($metaDescription) && $metaDescription !== '' ? $metaDescription : PublicSite::META_DESCRIPTION;
$canonicalUrl = isset($canonicalUrl) && $canonicalUrl !== '' ? $canonicalUrl : PublicSite::CANONICAL_URL;
$robots = isset($robotsMeta) && $robotsMeta !== '' ? $robotsMeta : 'index, follow';
$ogImage = rtrim(PublicSite::CANONICAL_URL, '/') . $assetsUrl . '/img/logobco.png';

$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => PublicSite::CANONICAL_URL . '#organization',
            'name' => PublicSite::ORG_NAME,
            'url' => PublicSite::CANONICAL_URL,
            'logo' => $ogImage,
        ],
        [
            '@type' => 'WebSite',
            '@id' => PublicSite::CANONICAL_URL . '#website',
            'url' => PublicSite::CANONICAL_URL,
            'name' => PublicSite::productName(),
            'publisher' => ['@id' => PublicSite::CANONICAL_URL . '#organization'],
            'inLanguage' => 'pt-BR',
        ],
        [
            '@type' => 'SoftwareApplication',
            'name' => PublicSite::productName(),
            'url' => PublicSite::CANONICAL_URL,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'publisher' => ['@id' => PublicSite::CANONICAL_URL . '#organization'],
            'creator' => [
                '@type' => 'Organization',
                'name' => PublicSite::DEVELOPER_SHORT_NAME,
                'url' => PublicSite::DEVELOPER_URL,
            ],
        ],
    ],
];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= htmlspecialchars($metaTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDescription) ?>" />
<meta name="robots" content="<?= htmlspecialchars($robots) ?>" />
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>" />
<link rel="icon" type="image/png" href="<?= htmlspecialchars($assetsUrl) ?>/img/logobco.png" />

<meta property="og:type" content="website" />
<meta property="og:site_name" content="<?= htmlspecialchars(PublicSite::ORG_NAME) ?>" />
<meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>" />
<meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>" />
<meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>" />
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>" />
<meta property="og:locale" content="pt_BR" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>" />
<meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>" />
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>" />

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" />
<link rel="stylesheet" href="<?= htmlspecialchars($assetsUrl) ?>/css/public.css?v=<?= urlencode($publicCssVersion) ?>" />

<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<?= $content ?>
<script src="<?= htmlspecialchars($assetsUrl) ?>/js/public.js?v=<?= urlencode($publicJsVersion) ?>" defer></script>
</body>
</html>
