<?php

namespace Drupal\hbk_cforge_mod\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/**
 * Provides a 'SliderBlock' block.
 *
 * @Block(
 *  id = "slider_block",
 *  admin_label = @Translation("Slider"),
 * )
 */
class SliderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // dd([$this->configuration]);
    $styleStorage = \Drupal::entityTypeManager()->getStorage('image_style');
    $styles = $styleStorage->loadMultiple();

    $image_styles = [];
    foreach ($styles as $style) {
      /** @var ImageStyle $style */
      $image_styles[$style->id()] = $style->label();
    }

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#options' => $image_styles,
      '#default_value' => $this->configuration['image_style'] ?? '',
    ];
    for ($i = 0; $i < 5; $i++) {
      $form['details' . $i] = [
        '#type' => 'details',
        '#title' => $this->t('Image ' . $i),
        '#open' => FALSE,
      ];
      $form['details' . $i]['image' . $i] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Images'),
        '#description' => $this->t('charger l\'image'),
        '#default_value' => $this->configuration['image' . $i],
        '#upload_validators' => [
          'file_validate_extensions' => ['jpg jpeg png gif webp'],
        ],
      ];
      $form['details' . $i]['description' . $i] = [
        "#type" => 'textarea',
        '#title' => $this->t("Description"),
        '#description' => $this->t("Description pour l'image chargée"),
        '#default_value' => $this->configuration['description' . $i],

      ];
      $form['details' . $i]['show_slide_' . $i] = [
        "#type" => 'checkbox',
        '#title' => $this->t("show slide"),
        '#description' => $this->t("whether or not this slide should be shown"),
        '#default_value' => $this->configuration['show_slide_' . $i] ? TRUE : FALSE,
      ];

      // '#weight' => '0', ; will be handled later
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    for ($i = 0; $i < 5; $i++) {
      $values = $form_state->getValue('details' . $i);
      $this->configuration['image' . $i] = $values['image' . $i];
      $this->configuration['description' . $i] = $values['description' . $i];
      $this->configuration['show_slide_' . $i] = $values['show_slide_' . $i];
    }
    $this->configuration["image_style"] = $form_state->getValue("image_style");
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'slider_block';
    $image_style = $this->configuration['image_style'] ?? '';

    for ($i = 0; $i < 5; $i++) {
      // Récupérer les informations de configuration
      $image = $this->configuration['image' . $i];
      $description = $this->configuration['description' . $i];
      $show_slide = $this->configuration['show_slide_' . $i];

      // Vérifier si un style d'image est défini

      // Vérifier si une image est définie
      if (!empty($image)) {
        // dd($image);
        $file = File::load($image[0]);

        if ($file) {
          // Générer l'URL de l'image avec le style d'image appliqué
          $image_url = $file->createFileUrl();

          if (!empty($image_style)) {
            $style = ImageStyle::load($image_style);
            $styled_image_url = $style->buildUrl($file->getFileUri());

            if ($styled_image_url) {
              $image_url = $styled_image_url;
            }
          }

          // Ajouter l'image à la structure de rendu
          // Ajouter la description et l'indicateur d'affichage à la structure de rendu
          $build['#content'][] = [
            "description" => $description,
            "show_slide" => $show_slide,
            "image" => [
              '#theme' => 'image',
              '#uri' => $image_url,
              '#alt' => 'Description de l\'image',
            ]
          ];
        }
      }
    }
    return $build;
  }
}
