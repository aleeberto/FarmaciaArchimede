<?php

namespace App\View;

use App\Core\PageBuilder;
use App\Config\Config;
use App\Core\Auth; // <-- importiamo il facade Auth
use RuntimeException;

/**
 * Class HeaderBuilder
 *
 * Costruisce il menu di navigazione utilizzando la configurazione 'paths.navigation'
 * o, se non definita, effettuando la discovery dei template. Se l'utente è loggato,
 * sostituisce la voce "Area personale" con il suo nome.
 */
class HeaderBuilder
{
    private PageBuilder $builder;
    private int $currentIndex;
    /** @var array<int,array{string,string}> */
    private array $pages;

    /**
     * @param PageBuilder $builder      Istanza del PageBuilder per caricare template e percorsi.
     * @param int         $currentIndex Indice corrente nella navigazione.
     */
    public function __construct(PageBuilder $builder, int $currentIndex = 0)
    {
        $this->builder      = $builder;
        $this->currentIndex = $currentIndex;

        $nav = Config::get('paths.navigation', []);
        if (is_array($nav) && count($nav) > 0) {
            // Usa la configurazione esplicita
            $this->pages = array_map(
                fn(array $item): array => [(string)($item[0] ?? '/'), (string)($item[1] ?? '')],
                $nav
            );
        } else {
            // Fallback alla discovery dei template
            $this->pages = $this->discoverPages();
        }
    }

    /**
     * Ritorna l'HTML dell'header con il menu di navigazione.
     * Sostituisce la voce "Area personale" con il nome dell'utente se loggato.
     *
     * @return string HTML dell'header.
     */
    public function build(): string
    {
        $tpl  = $this->builder->loadTemplate('header.html');
        $user = Auth::user();

        $html = '';
        foreach ($this->pages as $i => [$path, $title]) {
            $active = $i === $this->currentIndex ? ' class="active"' : '';
            $href   = $i === $this->currentIndex ? '' : " href=\"{$path}\"";

            if ($title === 'Accedi' && is_array($user)) {
                $display = $user['nome'] ?? $user['username'] ?? 'Profilo';
            } else {
                $display = $title;
            }

            $html .= "<li{$active}><a{$href}>{$display}</a></li>";
        }

        $tpl->insert('navigation', $html);
        return $tpl->build();
    }

    /**
     * Scandisce la cartella dei template per trovare le pagine da inserire nel menu.
     * Esclude i partial come head, header e footer.
     * Estrae il titolo dal <title> del template o genera un titolo dal filename.
     *
     * @return array<int,array{string,string}>
     */
    private function discoverPages(): array
    {
        $templatesDir = $this->builder->getBasePath();
        $files = glob($templatesDir . '/*.html');
        $menu = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (in_array($name, ['head', 'header', 'footer'], true)) {
                continue;
            }

            $path = $name === 'index' ? '/' : '/' . $name;
            $content = file_get_contents($file);
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $m)) {
                $title = $m[1];
            } else {
                $title = ucwords(str_replace(['-', '_'], ' ', $name));
            }

            $menu[] = [$path, $title];
        }

        return $menu;
    }
}
