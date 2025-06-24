<?php

namespace App\View;

use App\Core\PageBuilder;

class HeadBuilder
{
    private PageBuilder $builder;

    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function build(): string
    {
        $tpl = $this->builder->loadTemplate('head.html');
        return $tpl->build();
    }
}
