<?php

final class ManiphestTaskPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'TASK';

  public function getTypeName() {
    return pht('Maniphest Task');
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorManiphestApplication';
  }

  public function newObject() {
    return new ManiphestTask();
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new ManiphestTaskQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $task = $objects[$phid];
      $id = $task->getID();
      $title = $task->getTitle();

      $color_map = ManiphestTaskPriority::getColorMap();
      $bar_color = idx($color_map, $task->getPriority(), 'grey');

      $field_list = PhabricatorCustomField::getObjectFields(
        $task,
        PhabricatorCustomField::ROLE_EDIT);
      $field_list->readFieldsFromStorage($task);

      $aux_fields = $field_list->getFields();
      $tracks = array('0' => 'Task',
        '1' => 'Feature',
        '2' => 'Bug',
        '3' => 'UI',
        '4' => 'Improve',
        '5' => 'Hotfix'
      );
      $track_value = $aux_fields['std:maniphest:huaban:track']->getValueForStorage();
      $track = $tracks[$track_value ? $track_value : 0];
      $estimated_story_points = $aux_fields['std:maniphest:huaban:estimated-story-points']->getValueForStorage();

      $handle->setName("T{$id}");
      $handle->setFullName("T{$id}: {$title} · {$estimated_story_points} Points");
      $handle->setURI("/T{$id}");
      switch ($track) {
        case 'Feature':
          $handle->setIcon('fa-leaf');
          break;
        case 'Bug':
          $handle->setIcon('fa-bug');
          break;
        case 'UI':
          $handle->setIcon('fa-image');
          break;
        case 'Improve':
          $handle->setIcon('fa-arrow-circle-o-up');
          break;
        case 'Hotfix':
          $handle->setIcon('fa-bolt');
          break;
        default:
          $handle->setIcon('fa-tasks');
          break;
      }
      $handle->setIcon($handle->getIcon() . ' ' . $bar_color);


      if ($task->isClosed()) {
        $handle->setStatus(PhabricatorObjectHandle::STATUS_CLOSED);
      }
    }
  }

  public function canLoadNamedObject($name) {
    return preg_match('/^T\d*[1-9]\d*$/i', $name);
  }

  public function loadNamedObjects(
    PhabricatorObjectQuery $query,
    array $names) {

    $id_map = array();
    foreach ($names as $name) {
      $id = (int)substr($name, 1);
      $id_map[$id][] = $name;
    }

    $objects = id(new ManiphestTaskQuery())
      ->setViewer($query->getViewer())
      ->withIDs(array_keys($id_map))
      ->execute();

    $results = array();
    foreach ($objects as $id => $object) {
      foreach (idx($id_map, $id, array()) as $name) {
        $results[$name] = $object;
      }
    }

    return $results;
  }

}
