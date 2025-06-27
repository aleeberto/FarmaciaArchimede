<?php

namespace App\View;

use App\Core\PageBuilder;

/**
 * Costruisce il footer della pagina.
 */
class FooterBuilder
{
    private PageBuilder $builder;

    /**
     * FooterBuilder constructor.
     *
     * @param PageBuilder $builder Istanza di PageBuilder per il rendering del template.
     */
    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Genera l'HTML del footer a partire dal template 'footer.html'.
     *
     * @return string HTML del footer.
     */
    public function build(): string
    {
        $tpl = $this->builder->loadTemplate('footer.html');
        return $tpl->build();
    }
}

