<?php

namespace App\View;

use App\Core\PageBuilder;

class HeaderBuilder
{
    private PageBuilder $builder;
    private string $currentPath;

    public function __construct(PageBuilder $builder, string $currentPath = '/')
    {
        $this->builder = $builder;
        $this->currentPath = $currentPath ?: '/';
    }

    public function build(): string
    {
        $routes = [
            'home'      => 'index.php',
            'prodotti'  => 'prodotti.php',
            'chi_siamo' => 'chi_siamo.php',
            'contatti'  => 'contatti.php',
        ];

        $isLogged = false;
        $loginLabel = 'Accedi';
        $loginHref = 'login.php';

        try {
            $user = $this->builder->getAuthService()?->getUser();
            if ($user) {
                $isLogged = true;
                $loginLabel = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
                $loginHref = 'area_personale.php';
            }
        } catch (\Throwable) {
            $isLogged = false;
        }

        $currAbs = parse_url($this->currentPath, PHP_URL_PATH) ?: '/';
        $baseDir = $this->getBaseDir();
        $currRel = $this->absToRel($currAbs, $baseDir);

        $active = ['home'=>'','prodotti'=>'','chi_siamo'=>'','contatti'=>'','login'=>''];
        $aria   = ['home'=>'','prodotti'=>'','chi_siamo'=>'','contatti'=>'','login'=>''];

        foreach ($routes as $key => $relPath) {
            if ($this->sameRouteRel($currRel, $relPath)) {
                $active[$key] = 'active';
                $aria[$key] = 'aria-current="page"';
                break;
            }
        }
        if ($this->sameRouteRel($currRel, $loginHref)) {
            $active['login'] = 'active';
            $aria['login'] = 'aria-current="page"';
        }
        $loginAria = ($active['login'] === 'active') ? 'aria-current="page"' : '';

        $tpl = $this->builder->loadTemplate('common/header.html');

        $tpl->insert('menu.home.class',      $active['home']);
        $tpl->insert('menu.prodotti.class',  $active['prodotti']);
        $tpl->insert('menu.chi_siamo.class', $active['chi_siamo']);
        $tpl->insert('menu.contatti.class',  $active['contatti']);
        $tpl->insert('menu.login.class',     $active['login']);

        $tpl->insert('menu.home.aria',      $aria['home']);
        $tpl->insert('menu.prodotti.aria',  $aria['prodotti']);
        $tpl->insert('menu.chi_siamo.aria', $aria['chi_siamo']);
        $tpl->insert('menu.contatti.aria',  $aria['contatti']);

        $tpl->insert('menu.home.href',      $routes['home']);
        $tpl->insert('menu.prodotti.href',  $routes['prodotti']);
        $tpl->insert('menu.chi_siamo.href', $routes['chi_siamo']);
        $tpl->insert('menu.contatti.href',  $routes['contatti']);

        $tpl->insert('login.href',  $loginHref);
        $tpl->insert('login.label', $loginLabel);
        $tpl->insert('login.aria',  $loginAria);

        return $tpl->build();
    }

    private function getBaseDir(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/';
        $dir = rtrim(dirname($script), '/\\');
        return $dir === '' ? '/' : $dir;
    }

    private function absToRel(string $absPath, string $baseDir): string
    {
        $absPath = parse_url($absPath, PHP_URL_PATH) ?: '/';
        $baseDir = rtrim($baseDir, '/');
        if ($baseDir === '') $baseDir = '/';
        if ($absPath === '/' || $absPath === $baseDir || $absPath === $baseDir . '/') return 'index.php';
        if ($baseDir !== '/' && str_starts_with($absPath, $baseDir . '/')) {
            $rel = substr($absPath, strlen($baseDir) + 1);
        } else {
            $rel = ltrim($absPath, '/');
        }
        $rel = rtrim($rel, '/');
        return $rel === '' ? 'index.php' : $rel;
    }

    private function sameRouteRel(string $a, string $b): bool
    {
        $norm = static function (string $p): string {
            $p = trim($p);
            $p = ltrim($p, '/');
            $p = rtrim($p, '/');
            return ($p === '' || $p === 'index.php') ? 'index.php' : $p;
        };
        return $norm($a) === $norm($b);
    }
}