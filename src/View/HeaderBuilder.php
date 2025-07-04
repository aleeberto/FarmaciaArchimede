<?php
namespace App\View;

use App\Core\PageBuilder;

/**
 * Costruisce l'header della pagina e espone le voci di navbar.
 */
class HeaderBuilder
{
    private PageBuilder $builder;
    private string      $currentPath;
    private ?array      $navItems = null;

    public function __construct(PageBuilder $builder, string $currentPath = '/')
    {
        $this->builder     = $builder;
        // Normalizzo con slash iniziale
        $this->currentPath = '/'.trim($currentPath, '/');
    }

    /**
     * Ritorna array[path=>label] delle voci di navbar,
     * parsate dal risultato di build() così da includere
     * la sostituzione login→area_personale.
     *
     * @return string[]
     */
    public function getNavItems(): array
    {
        if ($this->navItems !== null) {
            return $this->navItems;
        }

        // Prendo l'HTML **completo** dell'header, con eventuali modifiche login→username e href→area_personale
        $html = $this->build();

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $links = $xpath->query('//nav//ul//li/a');

        $items = [];
        /** @var \DOMElement $a */
        foreach ($links as $a) {
            $href  = $a->getAttribute('href');
            $label = trim($a->textContent);

            // Normalizzo "index.php" a "/"
            if (preg_match('#(^|/)index\.php$#i', $href)) {
                $path = '/';
            } else {
                $path = '/'.ltrim($href, '/');
            }

            $items[$path] = $label;
        }

        return $this->navItems = $items;
    }

    /**
     * Costruisce l'HTML dell'header, sostituendo
     * Accedi→NomeUtente e, se loggato, login.php→area_personale.php
     */
    public function build(): string
    {
        // Carico il template
        $html = $this->builder->loadTemplate('header.html')->build();

        // 1) Se l'utente è loggato, sostituisco <a href="login.php">Accedi</a>
        //    con <a href="area_personale.php">NomeUtente</a>
        $auth = $this->builder->getAuthService();
        $user = $auth->getUser();
        if (is_array($user)) {
            $rawName = $user['Nome'] ?? $user['username'] ?? 'Utente';
            $username = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');

            // Cambio href e testo in un colpo solo
            $html = preg_replace(
                '#<a\s+href="login\.php"([^>]*)>.*?</a>#i',
                '<a href="area_personale.php"$1>'.$username.'</a>',
                $html
            );
        }

        // 2) Evidenzio la voce attiva (rimane come prima)
        $relPath = '/'.ltrim($this->currentPath, '/');
        $p       = preg_quote(trim($relPath, '/'), '#');
        $pattern = "#(<li\\b[^>]*>)(\\s*<a\\s+href=\"/?{$p}\"[^>]*>)#i";
        $html = preg_replace_callback($pattern, function(array $m) {
            $li = $m[1];
            if (preg_match('/\\bclass="([^"]*)"/', $li, $c)) {
                $li = preg_replace(
                    '/\\bclass="([^"]*)"/',
                    'class="'.trim($c[1].' active').'"',
                    $li
                );
            } else {
                $li = rtrim($li, '>').' class="active">';
            }
            return $li . $m[2];
        }, $html);

        return $html;
    }
}
