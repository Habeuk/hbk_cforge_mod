<?php

namespace Drupal\hbk_cforge_mod\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

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
   * @var FileUrlGeneratorInterface $fileUrlGenerator
   */
  protected $fileUrlGenerator;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_url_generator')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
      $form['details' . $i]['title' . $i] = [
        "#type" => "textfield",
        "#title" => $this->t("Title"),
        '#description' => $this->t('the title shown in the slider'),
        '#default_value' => $this->configuration['title' . $i] ?? "",
      ];
      $form['details' . $i]['call_to_action' . $i] = [
        '#type' => 'link',
        '#title' => $this->t('lien ' . $i),
        '#url' => Url::fromUri('internal:/chemin-de-mon-lien'),
      ];
      $form['details' . $i]['image' . $i] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Images'),
        '#description' => $this->t('charger l\'image'),
        '#default_value' => $this->configuration['image' . $i],
        '#upload_location' => 'public://sliders/',
        '#upload_validators' => [
          'file_validate_extensions' => ['jpg jpeg png gif webp'],
        ],
        '#required' => $this->configuration["show_slide_" . $i] ?? FALSE
      ];
      $form['details' . $i]['description' . $i] = [
        "#type" => 'textarea',
        '#title' => $this->t("Description"),
        '#description' => $this->t("Description pour l'image chargée"),
        '#default_value' => $this->configuration['description' . $i],

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
        '#required' => empty($this->configuration['call_to_action_link' . $i]) ? FALSE : TRUE,
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
        '#default_value' => $this->configuration['show_slide_' . $i] ? TRUE : FALSE,
      ];

      // '#weight' => '0', ; will be handled later
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Récupérez la valeur du champ de saisie de lien
    dump("yes");
    for ($i = 0; $i < 5; $i++) {
      $values = $form_state->getValue['details' . $i];

      $link = $values['call_to_action_link' . $i];
      $link_label = $values['call_to_action_label' . $i];
      // Vérifiez si le lien est valide
      if (!empty($link) && !filter_var($link, FILTER_VALIDATE_URL)) {
        $form_state->setErrorByName('link slide ' . $i, $this->t('Le lien saisi n\'est pas valide.'));
      } elseif (!empty($link) && empty($link_label)) {
        $form_state->setErrorByName('label link slide ' . $i, $this->t('the label of the link can\'t be empty if there is a link'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    for ($i = 0; $i < 5; $i++) {
      $values = $form_state->getValue('details' . $i);
      $this->configuration['title' . $i] = $values['title' . $i];
      $this->configuration['image' . $i] = $values['image' . $i];
      if ($values['image' . $i]) {
        # code...
        $fid = File::load($values['image' . $i][0]);
        $fid->setPermanent();
        $fid->save();
      }
      $this->configuration['description' . $i] = $values['description' . $i];
      $this->configuration['show_slide_' . $i] = $values['show_slide_' . $i];
      $this->configuration['call_to_action_label' . $i] = $values["call_to_action" . $i]['call_to_action_label' . $i];
      $this->configuration['call_to_action_link' . $i] = $values["call_to_action" . $i]['call_to_action_link' . $i];
    }
    $this->configuration["image_style"] = $form_state->getValue("image_style");
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'slider_block';
    $selected_image_style = $this->configuration['image_style'] ?? '';

    for ($i = 0; $i < 5; $i++) {
      // R￩cup￩rer les informations de configuration
      $title = $this->configuration['title' . $i];
      $image = $this->configuration['image' . $i];
      $description = $this->configuration['description' . $i];
      $show_slide = $this->configuration['show_slide_' . $i];
      $link = $this->configuration['call_to_action_link' . $i];
      $link_label = $this->configuration['call_to_action_label' . $i];
      // V￩rifier si un style d'image est d￩fini

      // V￩rifier si une image est d￩finie
      if (!empty($image)) {
        $file = File::load($image[0]);

        if ($file) {
          // G￩n￩rer le chemin de destination du fichier d￩riv￩

          // Ajouter l'image ￠ la structure de rendu
          // Ajouter la description et l'indicateur d'affichage ￠ la structure de rendu

          /**
           * @var ImageStyle
           */
          $imageStyle = ImageStyle::load($selected_image_style);
          $uri = $imageStyle->buildUrl($file->getFileUri());
          $build['sliders'][] = [
            "title" => $title,
            "description" => $description,
            "show_slide" => $show_slide,
            "link" => $link,
            "link_label" => $link_label,
            "image" =>
            [
              '#theme' => 'image',
              '#uri' => $uri,
              '#alt' => 'Description de l\'image',
            ]

          ];
        }
      }
    }
    return $build;
  }
}
