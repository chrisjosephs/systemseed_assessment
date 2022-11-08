<?php

namespace Drupal\systemseed_assessment\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\ParagraphAccessControlHandler;

/**
 * Add custom paragraphs_type access.
 */
class ToDoItemAccessControlHandler extends ParagraphAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $paragraph, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        // Permission to modify state of To-Do items (not the node!) should
        // be given only to the users who have access to view the checklist.
        // this in.
        if ($paragraph->getParagraphType()->id() === 'to_do_item') {
          if ($account->hasPermission('view todo_checklist revisions')) {
            return(AccessResult::allowed());
          }
          else {
            return(AccessResult::forbidden());
          }
        }
        break;

      default:
        return parent::checkAccess($paragraph, $operation, $account);
    }
  }

}
