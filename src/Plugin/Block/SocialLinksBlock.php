<?php

declare(strict_types=1);

namespace Drupal\hbk_cforge_mod\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hbk_cforge_mod\CforgePluginInterface;

/**
 * Provides a block text social link.
 *
 * @Block(
 *   id = "hbk_cforge_mod_block_social_links",
 *   admin_label = @Translation("Block Social links"),
 * )
 */
final class SocialLinksBlock extends CforgePlugininterface {

  private $socials;
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->socials = [
      'Facebook',
      'X',
      'Youtube',
      'Linkedin',
      'Github',
      'Instagram',
      'SnapChat',
      'Pinterest',
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $styles = ImageStyle::loadMultiple();
    $image_styles = [];
    foreach ($styles as $style) {
      /** @var ImageStyle $style */
      $image_styles[$style->id()] = $style->label();
    }
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('title'),
      '#default_value' => $this->configuration['title'] ?? "",
      '#required' => TRUE
    ];
    foreach ($this->socials as $value) {
      $form[strtolower($value)] = [
        '#type' => 'textfield',
        '#title' => $this->t("link for" . $value),
        '#default_value' => $this->configuration['socials'][strtolower($value)] ?? ""
      ];
    }

    return parent::blockForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    // $this->configuration = [];
    $image_config = $form_state->getValue('image_config');
    $this->configuration['title'] = $form_state->getValue("title");
    $this->configuration['socials'];
    foreach ($this->socials as $value) {
      $this->configuration['socials'][strtolower($value)] = $form_state->getValue(strtolower($value));
    }
    // dump($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $title = $this->configuration['title'];
    $socials = $this->configuration['socials'];
    $build = [
      "title" => $title,
      "socials" => $socials
    ];
    parent::completeBuild($build, $this->configuration);
    return $build;
  }
}
