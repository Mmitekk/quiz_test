<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a Quiz Test entity.
 *
 * @ingroup quiz_test
 */
class QuizTestDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Вы уверены, что хотите удалить тест «%name»? Все вопросы, связанные с этим тестом, также будут удалены.', [
      '%name' => $this->entity->label(),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    // Delete all associated questions.
    $questions = $entity->getQuestions();
    $question_storage = $this->entityTypeManager->getStorage('quiz_test_question');
    foreach ($questions as $question) {
      $question_storage->delete([$question]);
    }

    parent::submitForm($form, $form_state);
  }

}
