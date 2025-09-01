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
        $routes = [
            'home'      => '/',
            'prodotti'  => '/prodotti.php',
            'chi_siamo' => '/chi_siamo.php',
            'contatti'  => '/contatti.php',
        ];

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
            $isLogged = false;
        }

        $active = [
            'home'      => '',
            'prodotti'  => '',
            'chi_siamo' => '',
            'contatti'  => '',
            'login'     => '',
        ];

        $aria = [
            'home'      => '',
            'prodotti'  => '',
            'chi_siamo' => '',
            'contatti'  => '',
            'login'     => '',
        ];

        $curr = '/' . ltrim(parse_url($this->currentPath, PHP_URL_PATH) ?: '/', '/');
        $curr = rtrim($curr, '/') ?: '/';

        foreach ($routes as $key => $path) {
            $path = rtrim(parse_url($path, PHP_URL_PATH) ?: '/', '/') ?: '/';
            if ($this->sameRoute($curr, $path)) {
                $active[$key] = 'active';
                $aria[$key]   = 'aria-current="page"';
                break;
            }
        }

        if ($this->sameRoute($curr, '/login.php') || $this->sameRoute($curr, '/area_personale.php')) {
            $active['login'] = 'active';
            $aria['login']   = 'aria-current="page"';
        }

        $loginAria = ($active['login'] === 'active') ? 'aria-current="page"' : '';

        $tpl = $this->builder->loadTemplate('common/header.html');

        // classi
        $tpl->insert('menu.home.class',      $active['home']);
        $tpl->insert('menu.prodotti.class',  $active['prodotti']);
        $tpl->insert('menu.chi_siamo.class', $active['chi_siamo']);
        $tpl->insert('menu.contatti.class',  $active['contatti']);
        $tpl->insert('menu.login.class',     $active['login']);

        // aria-current (o stringa vuota)
        $tpl->insert('menu.home.aria',      $aria['home']);
        $tpl->insert('menu.prodotti.aria',  $aria['prodotti']);
        $tpl->insert('menu.chi_siamo.aria', $aria['chi_siamo']);
        $tpl->insert('menu.contatti.aria',  $aria['contatti']);

        // link login
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