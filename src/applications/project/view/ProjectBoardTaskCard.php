<?php

final class ProjectBoardTaskCard {

  private $viewer;
  private $task;
  private $owner;
  private $canEdit;

  public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }
  public function getViewer() {
    return $this->viewer;
  }

  public function setTask(ManiphestTask $task) {
    $this->task = $task;
    return $this;
  }
  public function getTask() {
    return $this->task;
  }

  public function setOwner(PhabricatorObjectHandle $owner = null) {
    $this->owner = $owner;
    return $this;
  }
  public function getOwner() {
    return $this->owner;
  }

  public function setCanEdit($can_edit) {
    $this->canEdit = $can_edit;
    return $this;
  }

  public function getCanEdit() {
    return $this->canEdit;
  }

  public function getItem() {
    $task = $this->getTask();
    $owner = $this->getOwner();
    $can_edit = $this->getCanEdit();

    $color_map = ManiphestTaskPriority::getColorMap();
    $bar_color = idx($color_map, $task->getPriority(), 'grey');

    $field_list = PhabricatorCustomField::getObjectFields(
      $task,
      PhabricatorCustomField::ROLE_EDIT);
    $field_list->setViewer($this->getViewer());
    $field_list->readFieldsFromStorage($task);

    $aux_fields = $field_list->getFields();
    $tracks = array('0' => 'Task',
      '1' => 'Feature',
      '2' => 'Bug',
      '3' => 'UI',
      '4' => 'Improve',
      '5' => 'Hotfix'
    );
    $track = $tracks[$aux_fields['std:maniphest:huaban:track']->getValueForStorage()];
    $estimated_story_points = $aux_fields['std:maniphest:huaban:estimated-story-points']->getValueForStorage();
    switch ($track) {
      case 'Feature':
        $icon = 'fa-leaf';
        break;
      case 'Bug':
        $icon = 'fa-bug';
        break;
      case 'UI':
        $icon = 'fa-pain-brush';
        break;
      case 'Improve':
        $icon = 'fa-arrow-circle-o-up';
        break;
      case 'Hotfix':
        $icon = 'fa-bolt';
        break;
      default:
        $icon = 'fa-tasks';
        break;
    }

    $card = id(new PHUIObjectItemView())
      ->setObjectName('T'.$task->getID())
      ->setHeader($task->getTitle())
      ->addIcon($icon . ' ' . $bar_color, pht($estimated_story_points))
      ->setGrippable($can_edit)
      ->setHref('/T'.$task->getID())
      ->addSigil('project-card')
      ->setDisabled($task->isClosed())
      ->setMetadata(
        array(
          'objectPHID' => $task->getPHID(),
        ))
      ->addAction(
        id(new PHUIListItemView())
          ->setName(pht('Edit'))
          ->setIcon('fa-pencil')
          ->addSigil('edit-project-card')
          ->setHref('/maniphest/task/edit/'.$task->getID().'/'))
      ->setBarColor($bar_color);

    if ($owner) {
      $card->addAttribute($owner->renderLink());
    }

    return $card;
  }

}
