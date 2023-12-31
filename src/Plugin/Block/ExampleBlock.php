<?php

namespace Drupal\hbk_cforge_mod\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "hbk_cforge_mod_example",
 *   admin_label = @Translation("Example"),
 *   category = @Translation("Hbk Cforge")
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }
}
