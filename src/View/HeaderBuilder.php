<?php
namespace App\View;

use App\Core\PageBuilder;

/**
 * Costruisce l'header della pagina con menu e selezione percorso corrente.
 */
class HeaderBuilder
{
    private PageBuilder $builder;
    private string $currentPath;

    /**
     * HeaderBuilder constructor.
     *
     * @param PageBuilder $builder  Istanza di PageBuilder per il rendering del template.
     * @param string      $currentPath Percorso corrente della pagina (es. '/contatti.php').
     */
    public function __construct(PageBuilder $builder, string $currentPath = '/')
    {
        $this->builder     = $builder;
        $this->currentPath = $currentPath;
    }

    /**
     * Genera l'HTML dell'header, aggiungendo la classe 'active' alla voce di menu corrispondente al percorso corrente.
     *
     * @return string HTML dell'header modificato.
     */
    public function build(): string
    {
        $html = $this->builder->loadTemplate('header.html')->build();

        $relPath = ltrim($this->currentPath, '/');

        $p = preg_quote($relPath, '#');
        $pattern = "#(<li\\b[^>]*>)(\\s*<a\\s+href=\"/?{$p}\"[^>]*>)#i";

        $html = preg_replace_callback($pattern, function(array $m) {
            $liTag = $m[1];

            if (preg_match('/\\bclass="([^\"]*)"/', $liTag, $cls)) {
                $liTag = preg_replace(
                    '/\\bclass="([^\"]*)"/',
                    'class="'.trim($cls[1].' active').'"',
                    $liTag
                );
            } else {
                $liTag = rtrim($liTag, '>') . ' class="active">';
            }

            return $liTag . $m[2];
        }, $html);

        return $html;
    }
}