<?php

declare(strict_types=1);

namespace Drupal\ambientimpact_paragraphs\Hooks;

use Drupal\ambientimpact_paragraphs\Entity\ParagraphParentContentEntityUri;
use Drupal\hux\Attribute\Hook;

/**
 * Entity hook implementations.
 */
class Entity {

  #[Hook('entity_type_build')]
  /**
   * Implements \hook_entity_type_build().
   *
   * This sets our own URI callback for 'paragraph' entity types if none is set
   * on the entity type. See the URI callback for more information.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityTypeBuildEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_paragraphs\Entity\ParagraphParentContentEntityUri::uriCallback()
   *   This is the URI callback method. Contains more information.
   *
   * @see \Drupal\Core\Entity\EntityType::getUriCallback()
   *   Gets any current URI callback for an entity type, returning null if none
   *   is set.
   *
   * @see \Drupal\Core\Entity\EntityType::setUriCallback()
   *   Sets the URI callback for an entity type.
   *
   * @see \hook_entity_type_build()
   *   Hook documentation.
   */
  public function entityTypeBuild(array &$entityTypes): void {

    if ($entityTypes['paragraph']->getUriCallback() !== null) {
      return;
    }

    $entityTypes['paragraph']->setUriCallback([
      ParagraphParentContentEntityUri::class,
      'uriCallback',
    ]);

  }

}
