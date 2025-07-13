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

        // Imposta voce 'active' in base al percorso
        $relPath = ltrim($this->currentPath, '/');
        $p = preg_quote($relPath, '#');
        $pattern = '#(<li\b[^>]*>)(\s*<a\s+href="/?'.$p.'"[^>]*>)#i';

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

        // Se l'utente è autenticato, sostituisce 'Accedi' con il suo nome
        $user = $this->builder->getAuthService()->getUser();
        if ($user) {
            // Prende il nome dall'UserDTO
            $username = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
            $html = preg_replace(
                '#<li>\s*<a\s+href="login.php">Accedi</a>\s*</li>#i',
                '<li><a href="area_personale.php">' . $username . '</a></li>',
                $html
            );
        }

        return $html;
    }
}
