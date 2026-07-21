<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a Quiz Test Question entity.
 *
 * @ingroup quiz_test
 */
class QuizTestQuestionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Вы уверены, что хотите удалить этот вопрос?');
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
    /** @var \Drupal\quiz_test\Entity\QuizTestQuestion $entity */
    $entity = $this->entity;
    return new Url('quiz_test.question_list', [
      'quiz_test' => $entity->getTestId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    /** @var \Drupal\quiz_test\Entity\QuizTestQuestion $entity */
    $entity = $this->entity;
    return new Url('quiz_test.question_list', [
      'quiz_test' => $entity->getTestId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $test_id = $entity->getTestId();

    parent::submitForm($form, $form_state);

    $form_state->setRedirect('quiz_test.question_list', ['quiz_test' => $test_id]);
  }

}
