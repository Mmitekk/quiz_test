<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Quiz Test Open Question entity.
 *
 * @ingroup quiz_test
 */
class QuizTestOpenQuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Hide the test_id field — it is set from the route parameter.
    $form['test_id']['#type'] = 'hidden';
    $form['test_id']['#value'] = $entity->getTestId();

    $form['actions']['#weight'] = 100;
    if (isset($form['actions']['delete'])) {
      $form['actions']['delete']['#weight'] = 101;
    }

    $form['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    if ($entity->isNew() && !empty($form_state->getValue('test_id'))) {
      $entity->set('test_id', $form_state->getValue('test_id'));
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Открытый вопрос создан.'));
        break;

      default:
        $this->messenger()->addMessage($this->t('Открытый вопрос сохранён.'));
    }

    $form_state->setRedirect('quiz_test.open_question_list', [
      'quiz_test' => $entity->getTestId(),
    ]);
  }

}
