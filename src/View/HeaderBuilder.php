<?php

namespace App\View;

use App\Core\PageBuilder;

class HeaderBuilder
{
    private PageBuilder $builder;
    private string $currentPath;

    public function __construct(PageBuilder $builder, string $currentPath = '/')
    {
        $this->builder     = $builder;
        $this->currentPath = $currentPath ?: '/';
    }

    public function build(): string
    {
        // Mappa voci di menu -> path
        $routes = [
            'home'      => '/',
            'prodotti'  => '/prodotti.php',
            'chi_siamo' => '/chi_siamo.php',
            'contatti'  => '/contatti.php',
        ];

        // Determina stato utente (best-effort)
        $isLogged   = false;
        $loginLabel = 'Accedi';
        $loginHref  = '/login.php';

        try {
            $user = $this->builder->getAuthService()?->getUser();
            if ($user) {
                $isLogged   = true;
                $loginLabel = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
                $loginHref  = '/area_personale.php';
            }
        } catch (\Throwable $e) {
            // degraded mode: lascia Accedi
            $isLogged = false;
        }

        // Classi active
        $active = [
            'home'      => '',
            'prodotti'  => '',
            'chi_siamo' => '',
            'contatti'  => '',
            'login'     => '',
        ];

        $curr = '/' . ltrim(parse_url($this->currentPath, PHP_URL_PATH) ?: '/', '/');

        foreach ($routes as $key => $path) {
            if ($this->sameRoute($curr, $path)) {
                $active[$key] = 'active';
                break;
            }
        }

        if ($this->sameRoute($curr, '/login.php') || $this->sameRoute($curr, '/area_personale.php')) {
            $active['login'] = 'active';
        }

        $loginAria = ($active['login'] === 'active') ? 'tabindex="-1" aria-disabled="true"' : '';

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