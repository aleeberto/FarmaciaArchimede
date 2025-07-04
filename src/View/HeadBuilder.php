<?php
namespace App\View;

use App\Core\PageBuilder;

/**
 * Costruisce la sezione head del documento e ne espone i metadati.
 */
class HeadBuilder
{
    private PageBuilder $builder;
    private ?string     $customTitle = null;
    private string      $defaultTitle = 'Farmacia Archimede';

    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Imposta un titolo personalizzato per la pagina.
     */
    public function setTitle(string $title): void
    {
        $this->customTitle = $title;
    }

    /**
     * Genera e ritorna l'HTML della sezione head,
     * sostituendo <component>title</component> con il tag <title>.
     */
    public function build(): string
    {
        $tpl = $this->builder->loadTemplate('head.html');

        // Determino quale titolo usare
        $titleToUse = $this->getTitle();

        // Preparo il tag <title>
        $titleTag = '<title>' . htmlspecialchars($titleToUse, ENT_QUOTES, 'UTF-8') . '</title>';
        $tpl->insert('title', $titleTag);

        return $tpl->build();
    }

    /**
     * Ritorna il titolo da usare: custom se impostato, altrimenti default.
     */
    public function getTitle(): string
    {
        return $this->customTitle !== null
            ? $this->customTitle
            : $this->defaultTitle;
    }
}
