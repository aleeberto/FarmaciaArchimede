<?php

namespace App\View;

use App\Core\PageBuilder;
use RuntimeException;

class HeadBuilder
{
    private PageBuilder $builder;

    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param array $meta Associativo con chiavi 'meta_title', 'meta_description', 'meta_keywords'
     * @return string
     */
    public function build(array $meta = []): string
    {
        $tpl = $this->builder->loadTemplate('common/head.html');

        // Imposta valori di default se non passati
        $defaults = [
            'meta_title'       => 'Farmacia Archimede',
            'meta_description' => 'Il meglio per la tua salute.',
            'meta_keywords'    => 'farmacia, salute, benessere'
        ];
        $data = array_merge($defaults, array_intersect_key($meta, $defaults));

        $tpl->insertAll($data);
        return $tpl->build();
    }
}