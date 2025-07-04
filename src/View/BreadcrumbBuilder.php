<?php
namespace App\View;

use App\Core\PageBuilder;
use App\View\HeadBuilder;
use App\View\HeaderBuilder;

class BreadcrumbBuilder
{
    private PageBuilder $builder;
    private string      $currentPath;

    public function __construct(PageBuilder $builder, string $currentPath = '/')
    {
        $this->builder     = $builder;
        $this->currentPath = '/'.trim($currentPath, '/');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function build(): string
    {
        $header    = new HeaderBuilder($this->builder, $this->currentPath);
        $rootItems = $header->getNavItems();
        // es. ['/' => 'Home', '/prodotti.php'=>'Prodotti', ..., '/area_personale.php'=>'Mario Rossi']

        if (!isset($_SESSION['breadcrumbs'])) {
            $_SESSION['breadcrumbs'] = [];
        }
        $trail = &$_SESSION['breadcrumbs'];

        if (isset($rootItems[$this->currentPath])) {
            $trail = [
                ['path'  => $this->currentPath,
                    'label' => $rootItems[$this->currentPath]]
            ];
        }

        else {
            if (empty($trail)) {
                $homeLabel = $rootItems['/'] ?? 'Home';
                $trail[]   = ['path'=>'/', 'label'=>$homeLabel];
            }
            $last = end($trail)['path'];
            if ($this->currentPath !== $last) {
                $paths = array_column($trail, 'path');
                if (false !== $pos = array_search($this->currentPath, $paths, true)) {
                    $trail = array_slice($trail, 0, $pos + 1);
                } else {
                    $head  = new HeadBuilder($this->builder);
                    $head->build();
                    $label = $head->getTitle()
                        ?: ucfirst(str_replace('_',' ',basename($this->currentPath,'.php')));
                    $trail[] = ['path'=>$this->currentPath,'label'=>$label];
                }
            }
        }

        $lastIndex = count($trail) - 1;
        $liHtml    = '';
        foreach ($trail as $i => $item) {
            $p = htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8');
            $l = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
            $liHtml .= $i < $lastIndex
                ? "<li><a href=\"$p\">$l</a></li>"
                : "<li><span aria-current=\"page\">$l</span></li>";
        }

        $tpl = $this->builder->loadTemplate('breadcrumb.html');
        $tpl->insert('breadcrumbs', $liHtml);
        return $tpl->build();
    }
}
