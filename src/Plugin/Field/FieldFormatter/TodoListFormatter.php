<?php

namespace Drupal\systemseed_assessment\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'ToDo List' formatter.
 *
 * @FieldFormatter(
 *   id = "systemseed_assessment_todo_list",
 *   label = @Translation("ToDo List"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class TodoListFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $cache_tags = ['node:' . $items->getEntity()->id()];
    $todo_list = [];
    $todo_list['nid'] = $items->getEntity()->id();
    // I have additionally disabled the checkbox on the jsx if no access.
    // Task says based on whether you can "view" parent node, not "update".
    $todo_list['disabled'] = !(\Drupal::currentUser()->isAuthenticated() &&
      $items->getEntity()->access('view', \Drupal::currentUser())
    );
    /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
    foreach ($items as $item) {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $item->entity;
      // Ensure the paragraph entity exists and the bundle is the one we
      // expect. Normally the widget should throw warning for other bundles,
      // but for sake of the assessment it can be simplified.
      if (empty($paragraph) || $paragraph->bundle() !== 'to_do_item') {
        continue;
      }
      // Sanity check. Ensure that the paragraph bundle contains the expected
      // fields.
      if (!$paragraph->hasField('field_completed') || !$paragraph->hasField('field_label')) {
        continue;
      }
      // Get value of the completion state of a To-Do item.
      $completed = $paragraph->get('field_completed')->getString();
      // Get processed text value of a To-Do item label.
      $label = $paragraph->get('field_label')->first()->getValue();
      $todo_list['items'][] = [
        'id' => (int) $paragraph->id(),
        'completed' => (bool) $completed,
        'label' => !empty($label) ? check_markup($label['value'], $label['format']) : '',
      ];
      $cache_tags[] = 'paragraph:' . $paragraph->id();
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'todo-list',
        'data-todo-list' => Json::encode($todo_list),
        'data-authenticated' => \Drupal::currentUser()->isAuthenticated() ? 'true' : FALSE,
      ],
      '#attached' => [
        'library' => ['systemseed_assessment/application'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
        'max-age' => -1,
      ],
    ];
  }

}
