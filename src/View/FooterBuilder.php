<?php

namespace App\View;

use App\Core\PageBuilder;

class FooterBuilder
{
    private PageBuilder $builder;

    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function build(): string
    {
        $tpl = $this->builder->loadTemplate('footer.html');
        return $tpl->build();
    }
}
