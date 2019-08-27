<?php

namespace App\View\Template;

use Pagerfanta\View\Template\DefaultTemplate;

class FASPagerTemplate extends DefaultTemplate
{
    static protected $defaultOptions = [
        'prev_message'       => '&laquo;',
        'next_message'       => '&raquo;',
        'css_disabled_class' => 'disabled',
        'css_dots_class'     => 'dots',
        'css_current_class'  => 'active',
        'dots_text'          => '...',
        'container_template' => '<nav class="pagination-nav">%pages%</nav>',
        'page_template'      => '<a href="%href%"%rel%>%text%</a>',
        'span_template'      => '<span class="%class%">%text%</span>',
        'rel_previous'       => 'prev',
        'rel_next'           => 'next'
    ];
}
