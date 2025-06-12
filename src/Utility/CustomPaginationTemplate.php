<?php

namespace App\Utility;

use Pagerfanta\View\Template\DefaultTemplate;

class CustomPaginationTemplate extends DefaultTemplate
{
    static protected $defaultOptions = array(
        'prev_message' => 'Prev',
        'next_message'  => 'Next',
        'css_disabled_class' => 'disabled',
        'css_dots_class' => 'dots',
        'css_current_class' => 'active',
        'dots_text' => '...',
        'container_template' => '<div class="pg-page">%pages%</div>',
        'page_template' => '<a href="%href%"%rel%>%text%</a>',
        'span_template' => '<a href="javascript:void(0);" class="%class%">%text%</a>',
        'rel_previous' => 'prev',
        'rel_next' => 'next'
    );
}
