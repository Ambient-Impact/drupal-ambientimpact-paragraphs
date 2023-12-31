<?php

declare(strict_types=1);

namespace Drupal\ambientimpact_paragraphs\Entity;

use Drupal\Core\Url;
use Drupal\paragraphs\Entity\ParagraphInterface;

/**
 * Paragraph entity parent content entity URI class.
 */
class ParagraphParentContentEntityUri {

  /**
   * Paragraph entity URI callback method.
   *
   * This callback returns the parent node's URI to fix an issue in Drupal core
   * that would cause a fatal error when attempting to get the paragraph entity
   * URL, which some modules sometimes do.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphInterface $paragraph
   *   A paragraph entity.
   *
   * @return \Drupal\Core\Url
   *   A Drupal Url object.
   *
   * @see \Drupal\paragraphs\Entity\Paragraph::getParentEntity()
   *   Gets the parent entity of the paragraph.
   *
   * @see \Drupal\Core\Url::fromRoute()
   *
   * @see https://www.drupal.org/project/paragraphs/issues/2826834
   *   Paragraphs issue detailing the error and linking to related core issues.
   *
   * @todo Should we check if $parent->getEntityTypeId() === 'node' before
   * returning the node route? What if a node is not the parent entity?
   */
  public static function uriCallback(ParagraphInterface $paragraph): Url {

    $parent = $paragraph->getParentEntity();

    // Only try to get the node ID if the method exists on the object. This also
    // ensures that we only try to use the method if it is indeed an object and
    // not null, which would otherwise cause a fatal error.
    if (\method_exists($parent, 'id')) {

      return Url::fromRoute('entity.node.canonical', ['node' => $parent->id()]);

    // No ID? Just return the node entity route without parameters to avoid a
    // fatal error as Drupal expects a Url object.
    } else {
      return Url::fromRoute('entity.node.canonical');
    }

  }

}
