<?php

namespace App\View;

use App\Core\PageBuilder;

class HeaderBuilder
{
    private PageBuilder $builder;
    private string $currentPath;
    private bool $safe;

    public function __construct(PageBuilder $builder, string $currentPath = '/', bool $safe = false)
    {
        $this->builder     = $builder;
        $this->currentPath = $currentPath ?: '/';
        $this->safe        = $safe;
    }

    public function build(): string
    {
        // Mappa voci di menu -> path
        $routes = [
            'home'      => '/',
            'prodotti'  => '/prodotti.php',
            'chi_siamo' => '/chi_siamo.php',
            'contatti'  => '/contatti.php',
            // 'login' è gestita separatamente (può diventare /area_personale.php)
        ];

        // Determina se l’utente è loggato (solo se non in safe mode)
        $isLogged = false;
        $loginLabel = 'Accedi';
        $loginHref  = '/login.php';

        if (!$this->safe) {
            $user = $this->builder->getAuthService()?->getUser();
            if ($user) {
                $isLogged   = true;
                $loginLabel = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
                $loginHref  = '/area_personale.php';
            }
        }

        // Costruisci classi "active"
        $active = [
            'home'      => '',
            'prodotti'  => '',
            'chi_siamo' => '',
            'contatti'  => '',
            'login'     => '',
        ];

        // Normalizza path corrente
        $curr = '/' . ltrim(parse_url($this->currentPath, PHP_URL_PATH) ?: '/', '/');

        // Attiva la voce corrispondente
        foreach ($routes as $key => $path) {
            if ($this->sameRoute($curr, $path)) {
                $active[$key] = 'active';
                break;
            }
        }

        // Gestione stato "login": attivo se siamo su login.php o area_personale.php
        if ($this->sameRoute($curr, '/login.php') || $this->sameRoute($curr, '/area_personale.php')) {
            $active['login'] = 'active';
        }

        // Attributi ARIA/tabindex per il link attivo (non cliccabile/focusabile)
        $loginAria = '';
        if ($active['login'] === 'active') {
            $loginAria = 'tabindex="-1" aria-disabled="true"';
        }

        // Inserisci placeholder nel template
        $tpl = $this->builder->loadTemplate('common/header.html');

        $tpl->insert('menu.home.class',      $active['home']);
        $tpl->insert('menu.prodotti.class',  $active['prodotti']);
        $tpl->insert('menu.chi_siamo.class', $active['chi_siamo']);
        $tpl->insert('menu.contatti.class',  $active['contatti']);
        $tpl->insert('menu.login.class',     $active['login']);

        $tpl->insert('login.href',  $loginHref);
        $tpl->insert('login.label', $loginLabel);
        $tpl->insert('login.aria',  $loginAria);

        return $tpl->build();
    }

    /**
     * Confronto “per rotta”: ignora eventuali slash finali.
     */
    private function sameRoute(string $a, string $b): bool
    {
        $norm = static function (string $p): string {
            $p = parse_url($p, PHP_URL_PATH) ?: '/';
            $p = '/' . ltrim($p, '/');
            return rtrim($p, '/') ?: '/';
        };
        return $norm($a) === $norm($b);
    }
}
