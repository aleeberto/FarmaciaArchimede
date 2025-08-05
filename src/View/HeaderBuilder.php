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
     * @param PageBuilder $builder     Istanza di PageBuilder per il rendering del template.
     * @param string      $currentPath Percorso corrente della pagina (es. '/contatti.php').
     */
    public function __construct(PageBuilder $builder, string $currentPath = '/')
    {
        $this->builder     = $builder;
        $this->currentPath = $currentPath;
    }

    /**
     * Genera l'HTML dell'header, aggiungendo la classe 'active' alla voce di menu corrispondente
     * al percorso corrente e sostituendo 'Accedi' con il nome dell'utente autenticato.
     *
     * @return string HTML dell'header modificato.
     */
    public function build(): string
    {
        // Carica template header
        $html = $this->builder->loadTemplate('common/header.html')->build();

        // Prima: sostituisci 'Accedi' con 'Area Personale' se autenticato
        $user = $this->builder->getAuthService()->getUser();
        if ($user) {
            $username = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');

            $loginPattern = '#<li>\s*<a([^>]*)href=["\']/?login\.php["\']([^>]*)>(.*?)</a>\s*</li>#is';
            $html = preg_replace_callback($loginPattern, function(array $m) use ($username) {
                $before = $m[1];
                $after  = $m[2];
                $inner  = $m[3];
                $newInner = str_replace('Accedi', $username, $inner);
                return '<li><a' . $before . ' href="/area_personale.php"' . $after . '>'
                    . $newInner
                    . '</a></li>';
            }, $html, 1);
        }

        // Poi: imposta voce 'active' in base al percorso
        $relPath = ltrim($this->currentPath, '/');
        $p = preg_quote($relPath, '#');
        $pattern = '#(<li\b[^>]*>)(\s*<a\s+href="/?'.$p.'"[^>]*>)#i';

        $html = preg_replace_callback($pattern, function(array $m) {
            $liTag = $m[1];
            if (preg_match('/\bclass="([^"]*)"/', $liTag, $cls)) {
                $liTag = preg_replace(
                    '/\bclass="([^"]*)"/',
                    'class="'.trim($cls[1].' active').'"',
                    $liTag
                );
            } else {
                $liTag = rtrim($liTag, '>') . ' class="active">';
            }
            return $liTag . $m[2];
        }, $html);

        // Rendi il link attivo non cliccabile
        $html = preg_replace_callback(
            '#<li\b([^>]*)class="([^"]*active[^"]*)"\s*>\s*<a\b([^>]*href="[^"]+"[^>]*)>(.*?)</a>\s*</li>#is',
            function(array $m) {
                list(, $liAttrs, $classes, $aAttrs, $label) = $m;
                if (!preg_match('/\btabindex\b/', $aAttrs)) {
                    $aAttrs .= ' tabindex="-1" aria-disabled="true"';
                }
                return "<li{$liAttrs}class=\"{$classes}\">"
                    . "<a{$aAttrs}>{$label}</a>"
                    . "</li>";
            },
            $html
        );

        return $html;
    }

}
