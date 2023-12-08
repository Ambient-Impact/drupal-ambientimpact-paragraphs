<?php

declare(strict_types=1);

namespace Drupal\ambientimpact_paragraphs\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\geshifilter\GeshiFilter;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Plugin implementation of the 'geshifield_bem' formatter.
 *
 * This alters the GeshiField output, replacing the default GeSHi classes with
 * BEM classes, and wraps the output in a render array for easier alteration.
 *
 * @FieldFormatter(
 *   id = "geshifield_bem",
 *   label = @Translation("GeshiField BEM classes"),
 *   field_types = {
 *     "geshifield"
 *   }
 * )
 */
class GeshiFieldBEMFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a GeshiFieldBEMFormatter object.
   *
   * @param string $pluginID
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
   */
  public function __construct(
    string  $pluginID,
    array   $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array   $settings,
    string  $label,
    string  $viewMode,
    array   $thirdPartySettings,
    protected readonly RendererInterface $renderer,
  ) {

    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings
    );

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginID,
    $pluginDefinition,
  ) {

    return new static(
      $pluginID,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
    );

  }

  /**
   * Unwrap the GeshiField container element contents.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   Symfony DomCrawler instance containing elements to unwrap.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *
   * @see \Drupal\geshifilter\GeshiFilterProcess::geshiProcess()
   *
   * @todo Alter a clone of the nodes and return that instead of altering the
   *   original nodes to prevent partial unwrapping in case of error?
   */
  protected function unwrapGeshiContainer(Crawler $crawler): Crawler {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $preCrawler = $crawler->filter('pre');

    // If we couldn't find a <pre> element or we found more than one, return the
    // Crawler as-is.
    if (
      !($preCrawler->getNode(0) instanceof \DOMNode) ||
      \count($preCrawler) > 1
    ) {
      return $crawler;
    }

    /** @var \DOMElement */
    $container = $preCrawler->getNode(0);

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $childNodesCrawler = $preCrawler->children();

    for ($i = 0; $container->childNodes->length > 0; $i++) {

      /** @var \DOMNode|false */
      $result = $container->parentNode->insertBefore(
        // Note that we always specify index "0" as we're basically removing the
        // first child each time, similar to \array_shift(), and the child list
        // updates each time we do this in the same way removing the bottom
        // most card in a deck of cards on each iteration.
        $container->childNodes->item(0),
        $container
      );

      // If \DOMNode::insertBefore() returned false, return the original
      // Crawler.
      if ($result === false) {
        return $crawler;
      }

    }

    // Remove the now-empty node.
    $container->parentNode->removeChild($container);

    return $childNodesCrawler;

  }

  /**
   * Replace GeSHi HTML class attributes with our own.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   Symfony DomCrawler instance containing elements to alter.
   *
   * @param string $removeClass
   *   A string class to remove.
   *
   * @param string|string[] $addClasses
   *   A string or array of string classes to insert.
   *
   * @see \Drupal\Core\Template\Attribute
   *   Used to abstract away the class removal/addition.
   */
  protected function replaceGeshiClassAttr(
    Crawler       $crawler,
    string        $removeClass,
    string|array  $addClasses,
  ): void {

    /** @var \Symfony\Component\DomCrawler\Crawler */
    $filteredCrawler = $crawler->filter('span.' . $removeClass);

    foreach ($filteredCrawler as $element) {

      /** @var \Drupal\Core\Template\Attribute */
      $attributes = (new Attribute([]))->addClass(
        \explode(' ', $element->getAttribute('class'))
      );

      $attributes->removeClass($removeClass)->addClass($addClasses);

      $element->setAttribute(
        'class', \trim(\implode(' ', $attributes->getClass()->value())),
      );

    }

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(
    FieldItemListInterface $items, $langCode,
  ): array {

    $elements = [];
    $enabledLanguages = GeshiFilter::getEnabledLanguages();
    $baseClass = 'code-highlighted';

    foreach ($items as $delta => $item) {

      $initialRenderArray = [
        '#theme'      => 'geshifield_default',
        '#language'   => $item->language,
        '#sourcecode' => $item->sourcecode,
      ];

      $languageHumanName = $enabledLanguages[$item->language];

      // Ideally, we wouldn't render here like the geshifield_default formatter
      // does, since it's against Drupal 8 best practices, but we need the
      // rendered GeSHi output. GeSHi doesn't provide any way to change the
      // class names so we have to parse and alter what it renders.
      $renderedCode = (string) $this->renderer->render($initialRenderArray);

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $rootCrawler = new Crawler(
        // The <div> is to prevent the PHP DOM automatically wrapping any
        // top-level text content in a <p> element.
        '<div id="ambientimpact-geshi-root">' . $renderedCode . '</div>',
      );

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $crawler = new Crawler(
        $this->unwrapGeshiContainer($rootCrawler)->getNode(0)->parentNode,
      );

      // Code highlight classes that have numerical variations.
      foreach ([
        're'  => 'reserved-word',
        'kw'  => 'keyword',
        'sy'  => 'symbol',
        'br'  => 'bracket',
        'nu'  => 'number',
        'co'  => 'comment',
        'me'  => 'method',
        'st'  => 'string',
      ] as $old => $new) {

        // Depending on the language, these can have up to (and possibly more
        // than) 5 variations.
        for ($i = 0; $i <= 5; $i++) {

          $this->replaceGeshiClassAttr(
            $crawler,
            $old . $i,
            [$baseClass . '__' . $new, $baseClass . '__' . $new .'--' . $i],
          );

        }

      }

      // Code highlight classes that do *not* have numerical variations.
      foreach ([
        'st_h'    => $baseClass . '__string',
        'coMULTI' => [
          $baseClass . '__comment',
          $baseClass . '__comment--multi-line',
        ],
      ] as $old => $new) {

        $this->replaceGeshiClassAttr($crawler, $old, $new);

      }

      $elements[$delta] = [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'class'       => [
            $baseClass,
            $baseClass . '--language-' . $item->language,
          ],
        ],
        // Pass the human name of the language and the base class along so that
        // any code altering the render array has these handy in a predictable
        // place.
        '#code_human_name'  => $languageHumanName,
        '#base_class'       => $baseClass,

        'pre' => [
          '#type'       => 'html_tag',
          '#tag'        => 'pre',
          '#attributes' => [
            'class'       => [
              $baseClass . '__code',
            ],
            'data-code-language-human-name'   => $languageHumanName,
            'data-code-language-machine-name' => $item->language,
          ],
          '#value' => $crawler->html(),
        ],
      ];
    }

    return $elements;

  }

}
