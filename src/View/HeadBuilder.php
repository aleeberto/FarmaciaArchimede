<?php

namespace App\View;

use App\Core\PageBuilder;

/**
 * Costruisce la sezione head del documento.
 */
class HeadBuilder
{
    private PageBuilder $builder;

    /**
     * HeadBuilder constructor.
     *
     * @param PageBuilder $builder Istanza di PageBuilder per il rendering del template.
     */
    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Genera l'HTML della sezione head a partire dal template 'head.html'.
     *
     * @return string HTML della sezione head.
     */
    public function build(): string
    {
        $tpl = $this->builder->loadTemplate('head.html');
        return $tpl->build();
    }
}

