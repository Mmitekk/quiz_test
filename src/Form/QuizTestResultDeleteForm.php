<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a Quiz Test Result entity.
 *
 * @ingroup quiz_test
 */
class QuizTestResultDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Вы уверены, что хотите удалить результат прохождения теста «%test» (@name, @date)?', [
      '%test' => $this->entity->getTestTitle(),
      '@name' => $this->entity->getFullName(),
      '@date' => \Drupal::service('date.formatter')->format($this->entity->getCreatedTime(), 'medium'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Удалить');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('quiz_test.results');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
