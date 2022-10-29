<?php

namespace Drupal\systemseed_assessment\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
   * @param Request $request
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post(request $request): ModifiedResourceResponse
  {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if ($this->currentUser->isAuthenticated() &&
      !$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    if($json = $request->getContent()){
      $array = json_decode($json, TRUE);
      if($array){

      }
      else{
        throw new \JsonException();
      }
    }
    return new ModifiedResourceResponse($data = "hello", 200);
  }
  /** * {@inheritdoc} */
  public function permissions(): array
  {
    return [];
  }
}
