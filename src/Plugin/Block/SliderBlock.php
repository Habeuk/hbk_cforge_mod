<?php

namespace Drupal\hbk_cforge_mod\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;

/**
 * Provides a 'SliderBlock' block.
 *
 * @Block(
 *  id = "slider_block",
 *  admin_label = @Translation("Slider"),
 * )
 */
class SliderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   *
   * @var FileUrlGeneratorInterface $fileUrlGenerator
   */
  protected $fileUrlGenerator;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('file_url_generator'));
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public function defaultConfiguration() {
    return [
      'image_style' => 'image_1900x600',
      'show_svg' => true,
      'slide_conf' => [
        'effect' => 'slide',
        'loop' => false,
        'autoplay_delay' => 5000
      ]
    ] + parent::defaultConfiguration();
  }

  /**
   *
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
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
      '#default_value' => $this->configuration['image_style'] ?? ''
    ];
    $form['show_svg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Afficher le cover'),
      '#default_value' => $this->configuration['show_svg'] ?? true
    ];
    $form['slide_conf'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration du slider'),
      '#open' => false,
      '#tree' => true
    ];
    $form['slide_conf']['effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Effet'),
      '#options' => [
        'slide' => 'slide',
        "fade" => 'fade'
      ],
      '#default_value' => $this->configuration['slide_conf']['effect'] ?? 'slide'
    ];
    $form['slide_conf']['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#default_value' => $this->configuration['slide_conf']['loop'] ?? false
    ];
    $form['slide_conf']['autoplay_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay in milliseconds'),
      '#min' => 2500,
      '#default_value' => isset($this->configuration['slide_conf']['autoplay_delay']) ? $this->configuration['slide_conf']['autoplay_delay'] : 8000
    ];

    for ($i = 0; $i < 5; $i++) {
      $form['details' . $i] = [
        '#type' => 'details',
        '#title' => $this->t('Image ' . $i),
        '#open' => FALSE
      ];
      $form['details' . $i]['title' . $i] = [
        "#type" => "textfield",
        "#title" => $this->t("Title"),
        '#description' => $this->t('the title shown in the slider'),
        '#default_value' => $this->configuration['title' . $i] ?? ""
      ];
      $form['details' . $i]['call_to_action' . $i] = [
        '#type' => 'link',
        '#title' => $this->t('lien ' . $i),
        '#url' => Url::fromUri('internal:/chemin-de-mon-lien')
      ];
      $form['details' . $i]['image' . $i] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Images'),
        '#description' => $this->t('charger l\'image'),
        '#default_value' => $this->configuration['image' . $i] ?? null,
        '#upload_location' => 'public://sliders/',
        '#upload_validators' => [
          'file_validate_extensions' => [
            'jpg jpeg png gif webp'
          ]
        ],
        '#required' => $this->configuration["show_slide_" . $i] ?? FALSE
      ];
      $form['details' . $i]['description' . $i] = [
        "#type" => 'text_format',
        '#title' => $this->t("Description"),
        '#description' => $this->t("Description pour l'image chargée"),
        '#format' => $this->configuration["body"]["format"] ?? 'full_html',
        '#default_value' => $this->configuration['description' . $i]["value"] ?? ""
      ];
      $form['details' . $i]['call_to_action' . $i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('button configuration'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE
      ];
      $form['details' . $i]['call_to_action' . $i]['call_to_action_label' . $i] = [
        '#type' => 'textfield',
        '#title' => $this->t('button label'),
        '#default_value' => $this->configuration['call_to_action_label' . $i] ?? '',
        '#required' => empty($this->configuration['call_to_action_link' . $i]) ? FALSE : TRUE
      ];
      $form['details' . $i]['call_to_action' . $i]['call_to_action_link' . $i] = [
        '#type' => 'textfield',
        '#title' => $this->t('button link'),
        '#default_value' => $this->configuration['call_to_action_link' . $i] ?? ''
      ];
      $form['details' . $i]['show_slide_' . $i] = [
        "#type" => 'checkbox',
        '#title' => $this->t("show slide"),
        '#description' => $this->t("whether or not this slide should be shown"),
        '#default_value' => $this->configuration['show_slide_' . $i] ?? FALSE
      ];
      // '#weight' => '0', ; will be handled later
    }
    return $form;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    for ($i = 0; $i < 5; $i++) {
      $values = $form_state->getValue('details' . $i);
      $this->configuration['title' . $i] = $values['title' . $i] ?? "";
      $this->configuration['image' . $i] = $values['image' . $i] ?? null;
      if ($values['image' . $i]) {
        # code...
        $fid = File::load($values['image' . $i][0]);
        $fid->setPermanent();
        $fid->save();
      }
      $this->configuration['description' . $i] = $values['description' . $i] ?? null;
      $this->configuration['show_slide_' . $i] = $values['show_slide_' . $i] ?? null;
      $this->configuration['call_to_action_label' . $i] = $values["call_to_action" . $i]['call_to_action_label' . $i] ?? null;
      $this->configuration['call_to_action_link' . $i] = $values["call_to_action" . $i]['call_to_action_link' . $i] ?? null;
    }
    $this->configuration["image_style"] = $form_state->getValue("image_style") ?? null;
    $this->configuration['show_svg'] = $form_state->getValue('show_svg');
    $this->configuration['slide_conf'] = $form_state->getValue('slide_conf');
  }

  /**
   *
   * {@inheritdoc}
   */
  public function build() {
    $sliders = [];
    $selected_image_style = $this->configuration['image_style'] ?? '';

    for ($i = 0; $i < 5; $i++) {
      // R￩cup￩rer les informations de configuration
      $title = $this->configuration['title' . $i] ?? null;
      $image = $this->configuration['image' . $i] ?? null;
      $description = $this->configuration['description' . $i] ?? null;
      $show_slide = $this->configuration['show_slide_' . $i] ?? null;
      $link = $this->configuration['call_to_action_link' . $i] ?? null;
      $link_label = $this->configuration['call_to_action_label' . $i] ?? null;
      $show_svg = $this->configuration['show_svg'] ?? false;
      // V￩rifier si un style d'image est d￩fini

      // V￩rifier si une image est d￩finie
      if (!empty($image)) {
        $file = File::load($image[0]);

        if ($file) {
          // G￩n￩rer le chemin de destination du fichier d￩riv￩

          // Ajouter l'image ￠ la structure de rendu
          // Ajouter la description et l'indicateur d'affichage ￠ la structure
          // de rendu

          /**
           *
           * @var ImageStyle
           */
          $sliders[] = [
            "title" => $title,
            "description" => $description,
            "show_slide" => $show_slide,
            "link" => $link,
            "link_label" => $link_label,
            "image" => [
              '#theme' => 'image_style',
              '#style_name' => $selected_image_style,
              '#uri' => $file->getFileUri()
            ],
            'show_svg' => $show_svg
          ];
        }
      }
    }
    // dump($build);
    $Attribute = new Attribute();
    $Attribute->addClass("swiper-full-options", "cforge-slider-container");

    $dataSwiper = [
      'direction' => 'horizontal',
      'effect' => $this->configuration['slide_conf']['effect'],
      'speed' => 500,
      'spaceBetween' => 0,
      'loop' => (bool) $this->configuration['slide_conf']['loop'],
      'pagination' => [
        'el' => '.swiper-pagination',
        'type' => 'bullets',
        'clickable' => true
      ],
      // 'navigation' => [
      // 'nextEl' => '.swiper-button-next',
      // 'prevEl' => '.swiper-button-prev'
      // ],
      "autoplay" => [
        'delay' => (int) $this->configuration['slide_conf']['autoplay_delay'],
        'pauseOnMouseEnter' => true,
        'disableOnInteraction' => false
      ]
      // 'centeredSlides' => false
    ];
    $Attribute->setAttribute("data-swiper", Json::encode($dataSwiper));

    return [
      'content' => [
        '#theme' => 'slider_block',
        '#sliders' => $sliders,
        '#attributes' => $Attribute
      ]
    ];
  }

}
