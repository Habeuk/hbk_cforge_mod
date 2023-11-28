<?php

namespace Drupal\hbk_cforge_mod;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for complete form of cforge plugin block.
 */
abstract class CforgePlugininterface extends BlockBase {

    public function blockForm($form, FormStateInterface $form_state): array {
        $form["bg_style"] = [
            '#type' => 'select',
            '#title' => $this->t('Background style'),
            '#options' => [
                "cforge-bg-light" => $this->t("light"),
                "cforge-bg-gray-200" => $this->t("200 Gray"),
                "cforge-bg-gray-300" => $this->t("300 Gray"),
                "cforge-bg-gray-400" => $this->t("400 Gray"),
            ],
            '#default_value' => $this->configuration['bg_style'] ?? '',
        ];
        return $form;
    }

    /**
     * @return array
     */
    public function getValuesToSubmit(): array {
        return [
            "bg_style"
        ];
    }
}
