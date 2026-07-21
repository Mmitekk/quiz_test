<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Quiz Test edit forms.
 *
 * @ingroup quiz_test
 */
class QuizTestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Тест «%label» создан.', [
          '%label' => $entity->label(),
        ]));
        $form_state->setRedirect('entity.quiz_test.edit_form', ['quiz_test' => $entity->id()]);
        break;

      default:
        $this->messenger()->addMessage($this->t('Тест «%label» сохранён.', [
          '%label' => $entity->label(),
        ]));
        $form_state->setRedirect('entity.quiz_test.edit_form', ['quiz_test' => $entity->id()]);
    }
  }

}
