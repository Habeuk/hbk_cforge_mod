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
 * Provides a block text image block.
 *
 * @Block(
 *   id = "hbk_cforge_mod_block_text_image",
 *   admin_label = @Translation("Block Text Image"),
 * )
 */
final class BlockTextImageBlock extends CforgePlugininterface implements ContainerFactoryPluginInterface {

  private $renderer;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Renderer $renderer) {
    $this->renderer = $renderer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'example' => $this->t('Hello world!'),
    ];
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
    // dump(ImageStyle::loadMultiple());
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('title'),
      '#default_value' => $this->configuration['title'] ?? "",
      '#required' => TRUE
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#format' => 'full_html',
      '#default_value' => $this->configuration['body'],
      '#required' => TRUE
    ];

    $form['image_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image configuration'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE
    ];
    $form['image_config']['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#description' => $this->t('charger l\'image'),
      '#default_value' => $this->configuration['image'],
      '#upload_location' => 'public://image-left/',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png gif webp'],
      ],
      '#required' => TRUE
    ];
    $form['image_config']['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $image_styles,
      '#default_value' => $this->configuration['image_style'] ?? '',
    ];
    return parent::blockForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $image_config = $form_state->getValue('image_config');
    $this->configuration['title'] = $form_state->getValue("title");
    $this->configuration['body'] = $form_state->getValue('body');
    $this->configuration['image'] = $image_config["image"];
    $this->configuration['image_style'] = $image_config["image_style"];
    // dump($this->configuration);
    $fid = File::load($image_config['image'][0]);
    $fid->setPermanent();
    $fid->save();
    // foreach ($this->getValuesToSubmit() as $value) {
    //   $this->configuration[$value] = $form_state->getValue($value);
    // }
    parent::completeSubmit($this->configuration, $form_state);
    // dump($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $title = $this->configuration['title'];
    $body = $this->configuration['body'];
    $bg_style = $this->configuration["bg_style"];

    //loading and converting the image in the selected format
    $file = File::load($this->configuration["image"][0]);
    $image_style = ImageStyle::load($this->configuration["image_style"]);
    $image_uri = $image_style->buildUrl($file->getFileUri());
    $image = [
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#alt' => 'image',
    ];
    $build = [
      "bg_style" => $bg_style,
      "title" => $title,
      "body" => $body,
      "image" => $image,
    ];
    parent::completeBuild($build, $this->configuration);
    return $build;
  }
}
