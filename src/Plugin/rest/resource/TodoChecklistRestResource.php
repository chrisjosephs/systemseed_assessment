<?php

namespace Drupal\systemseed_assessment\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "todo_checklist_rest_resource",
 *   label = @Translation("Todo list rest resource"),
 *   uri_paths = {
 *     "canonical" = "/api/todo_checklist/{nid}"
 *   }
 * )
 */
class TodoChecklistRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('systemseed_assessment');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Responds to PATCH requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request object with body containing ToDoItem data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse|\Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
  public function patch(Request $request) {
    if (!$this->currentUser->isAuthenticated()) {
      return new ModifiedResourceResponse("User not logged in.", 401);
    }
    if ($json = $request->getContent()) {
      try {
        $array = json_decode($json, TRUE);
      }
      catch (\JsonException $exception) {
        return new ModifiedResourceResponse($exception->getMessage(), 400);
      }
      // Validate node type is a todo_checklist.
      $node_type = "todo_checklist";
      $nid = \Drupal::routeMatch()->getParameters()->get('nid');
      $node = Node::load($nid);
      if ($node->getType() !== $node_type) {
        throw Exception("Node should be of EntityType %et but found %it",
          [
            '%et' => $node_type,
            '%it' => $node->getEntityType(),
          ]);
      }
      // Permission to modify state of To-Do items (not the node!) should
      // be given only to the users who have access to view the checklist.
      if (!($node->access('view', $this->currentUser))) {
        return new ResourceResponse("User does not have access to view the checklist", 403);
      }
      try {
        $this->updateTodoItem($array);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
        $this->logger->error($e->getMessage());
        return new ModifiedResourceResponse("Entity saving exception.", 500);
      }
    }
    return new ModifiedResourceResponse("Success", 200);
  }

  /**
   * Update ToDoItem paragraphs with new data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateTodoItem($todoItem) {
    $entity_type = "to_do_item";
    $todoItemParagraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($todoItem['id']);
    if ($todoItemParagraph->getType() !== $entity_type) {
      throw Exception("Entity should be of EntityType %et but found %it",
        [
          '%et' => $entity_type,
          '%it' => $todoItemParagraph->getEntityType(),
        ]);
    }
    $todoItemParagraph->set('field_completed', $todoItem['completed'] ? '1' : '0');
    $todoItemParagraph->save();
    // This might be far too verbose IRL, but added for completeness:
    $this->logger->notice('User %u, updated todoItem with ID %id.',
      [
        '%u' => $this->currentUser->id(),
        '%id' => $todoItem['id'],
      ]);
    \Drupal::service('cache_tags.invalidator')
      ->invalidateTags(['paragraph:' . $todoItem['id']]);
  }

  /**
   * {@inheritDoc}
   */
  public function permissions(): array {
    $permissions = [];
    $definition = $this
      ->getPluginDefinition();
    foreach ($this
      ->availableMethods() as $method) {
      $lowered_method = strtolower($method);
      $permissions["restful {$lowered_method} {$this->pluginId}"] = [
        'title' => $this
          ->t('Access @method on %label resource', [
            '@method' => $method,
            '%label' => $definition['label'],
          ]),
      ];
    }
    return $permissions;
  }

}
