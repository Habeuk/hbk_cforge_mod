<?php


function template_preprocess_hbk_cforge_slider_block(&$vars) {
    $vars['item'] = [];
    if (!empty($vars['content']['#item'])) {
        $vars['item'] = $vars['content']['#item'];
    }
}
