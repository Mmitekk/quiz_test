<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list builder for Quiz Test entities.
 */
class QuizTestListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = [
      'data' => $this->t('ID'),
      'field' => 'id',
      'specifier' => 'id',
    ];
    $header['title'] = [
      'data' => $this->t('Название'),
      'field' => 'title',
      'specifier' => 'title',
    ];
    $header['questions'] = [
      'data' => $this->t('Вопросы'),
    ];
    $header['status'] = [
      'data' => $this->t('Статус'),
      'field' => 'status',
      'specifier' => 'status',
    ];
    $header['author'] = [
      'data' => $this->t('Автор'),
      'field' => 'uid',
      'specifier' => 'uid',
    ];
    $header['created'] = [
      'data' => $this->t('Создано'),
      'field' => 'created',
      'specifier' => 'created',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\quiz_test\Entity\QuizTest $entity */
    $row['id'] = $entity->id();
    $row['title'] = [
      'data' => [
        '#type' => 'link',
        '#title' => $entity->label(),
        '#url' => $entity->toUrl('edit-form'),
      ],
    ];

    // Count questions.
    $query = \Drupal::entityTypeManager()->getStorage('quiz_test_question')->getQuery()
      ->condition('test_id', $entity->id())
      ->count()
      ->accessCheck(TRUE);
    $count = $query->execute();
    $row['questions'] = $count;

    $row['status'] = $entity->isPublished() ? $this->t('Опубликовано') : $this->t('Не опубликовано');
    $row['author'] = $entity->getOwner()->getDisplayName();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = \Drupal::request()->query->get('destination');
    foreach ($operations as $key => $operation) {
      if (!isset($operations[$key]['query'])) {
        $operations[$key]['query'] = [];
      }
      if ($destination) {
        $operations[$key]['query']['destination'] = $destination;
      }
      // Translate operation titles.
      if ($key === 'edit') {
        $operations[$key]['title'] = $this->t('Редактировать');
      }
      if ($key === 'delete') {
        $operations[$key]['title'] = $this->t('Удалить');
      }
    }

    // Add "Questions" operation.
    $operations['questions'] = [
      'title' => $this->t('Вопросы'),
      'weight' => 10,
      'url' => Url::fromRoute('quiz_test.question_list', [
        'quiz_test' => $entity->id(),
      ]),
    ];

    // Add "View" operation to go to the public test page.
    if ($entity->isPublished()) {
      $operations['view'] = [
        'title' => $this->t('Просмотр'),
        'weight' => 15,
        'url' => Url::fromRoute('quiz_test.take_test', [
          'quiz_test' => $entity->id(),
        ]),
      ];
    }

    return $operations;
  }

}
