<?php

declare(strict_types=1);

namespace Drupal\ambientimpact_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\ambientimpact_paragraphs\Plugin\Field\FieldFormatter\GeshiFieldBemFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'geshifield_bem_paragraphs' formatter.
 *
 * This builds on the 'geshifield_bem' formatter with specific changes for
 * Paragraphs items:
 *   - Adds a heading to the output.
 *
 * @FieldFormatter(
 *   id = "geshifield_bem_paragraphs",
 *   label = @Translation("GeshiField BEM classes for Paragraphs"),
 *   field_types = {
 *     "geshifield"
 *   }
 * )
 */
class GeshiFieldBemParagraphsFormatter extends GeshiFieldBemFormatter {

  use StringTranslationTrait;

  /**
   * Constructs a GeshiFieldBEMParagraphsFormatter object.
   *
   * @param string $pluginId
   *   The plugin_id for the formatter.
   *
   * @param mixed $pluginDefinition
   *   The plug-in implementation definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @param string $label
   *   The formatter label display setting.
   *
   * @param string $viewMode
   *   The view mode.
   *
   * @param array $thirdPartySettings
   *   Any third party settings settings.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    string  $pluginId,
    array   $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings,
    string  $label,
    string  $viewMode,
    array   $thirdPartySettings,
    RendererInterface $renderer,
    TranslationInterface $stringTranslation,
  ) {

    parent::__construct(
      $pluginId, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings,
      $renderer,
    );

    $this->stringTranslation = $stringTranslation;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition,
  ) {

    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('string_translation'),
    );

  }

  /**
   * {@inheritdoc}
   *
   * @todo Port the tab size from Drupal 7.
   */
  public function viewElements(
    FieldItemListInterface $items, $langCode,
  ): array {

    $elements = parent::viewElements($items, $langCode);

    foreach ($elements as $delta => &$element) {

      $baseClass          = $element['#base_class'];
      $languageHumanName  = $element['#code_human_name'];

      // Correct this to how JavaScript is supposed to be capitalized.
      if ($languageHumanName === 'Javascript') {
        $languageHumanName = $this->t('JavaScript');
      }

      // Rename HTML5 to just HTML, as that's the default standard nowadays.
      if ($languageHumanName === 'HTML5') {
        $languageHumanName = $this->t('HTML');
      }

      // Update the data attribute in case we've changed the name.
      $element['pre']['#attributes']['data-code-language-human-name'] =
        $languageHumanName;

      $element['heading'] = [
        '#type'       => 'html_tag',
        '#tag'        => 'h3',
        '#attributes' => [
          'class'       => [
            $baseClass . '__heading',
          ],
          'title'       => $this->t(
            'Code language: @name',
            ['@name' => $languageHumanName]
          ),
        ],
        '#value'      => $this->t(
            '<span class="visually-hidden">Code language: </span>@name',
            ['@name' => $languageHumanName]
          ),
        '#weight'     => -1,
      ];

    }

    return $elements;

  }

}
