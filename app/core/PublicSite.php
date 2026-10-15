<?php
namespace App\Core;

/**
 * Ponto único de configuração da landing page pública (fora da área
 * autenticada): URL canônica, nome do produto/organização/desenvolvedora e
 * metadados de SEO padrão. Nenhuma view pública deve hardcodar esses valores
 * separadamente - sempre passar por aqui, para evitar divergência entre o
 * <title>, o JSON-LD, o sitemap e o rodapé.
 *
 * Três identidades distintas (não confundir):
 * - Instituto Dona: organização proprietária.
 * - DONA VIVA+ DIGITAL: produto (o sistema em si).
 * - TRAXTER. — Automações e Sistemas: desenvolvedora da plataforma.
 */
final class PublicSite
{
    /** URL pública canônica (produção). */
    public const CANONICAL_URL = 'https://donaconsultorias.com.br/';

    public const ORG_NAME = 'Instituto Dona';

    public const DEVELOPER_NAME = 'TRAXTER. — Automações e Sistemas';
    public const DEVELOPER_SHORT_NAME = 'TRAXTER.';
    public const DEVELOPER_URL = 'https://traxter.com.br/';

    public const META_DESCRIPTION = 'DONA VIVA+ DIGITAL é a plataforma do Instituto Dona para gestão, governança e desenvolvimento organizacional: indicadores, avaliações, cronogramas, planos de ação e auditorias em um só ambiente.';

    /** "DONA " + AppBrand::displayName() - único lugar que monta esse nome. */
    public static function productName(): string
    {
        return 'DONA ' . AppBrand::displayName();
    }
}
