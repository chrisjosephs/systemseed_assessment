<?php

namespace Drupal\systemseed_assessment\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "todo_list_rest_resource",
 *   label = @Translation("Todo list rest resource"),
 *   uri_paths = {
 *     "create" = "/api/todolist"
 *   }
 * )
 */
class TodoListRestResource extends ResourceBase {

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
   * Responds to POST requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request object with body containing ToDoItem data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post(Request $request): ModifiedResourceResponse {
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
      $node = Node::load($array['nid']);
      // Permission to modify state of To-Do items (not the node!) should
      // be given only to the users who have access to view the checklist.
      if (!($node->access('view', $this->currentUser))) {
        return new ModifiedResourceResponse("User does not have access to view the checklist", 403);
      }
      try {
        $this->updateTodoItem($array);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
        throw new HttpException(500, 'Internal Server Error', $e);
      }
    }
    return new ModifiedResourceResponse($data = "Success", 200);
  }

  /**
   * Update ToDoItem paragraphs with new data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateTodoItem($todoItem) {
    $todoItemParagraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($todoItem['id']);
    $todoItemParagraph->set('field_completed', $todoItem['completed'] ? '1' : '0');
    $todoItemParagraph->save();
    \Drupal::service('cache_tags.invalidator')
      ->invalidateTags(['paragraph:' . $todoItem['id']]);
    \Drupal::service('cache_tags.invalidator')
      ->invalidateTags(['node:' . $todoItem['nid']]);
  }

  /**
   * {@inheritDoc}
   */
  public function permissions(): array {
    return [];
  }

}
