<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Quiz Test Question edit forms.
 *
 * @ingroup quiz_test
 */
class QuizTestQuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // Hide the test_id field (it is set from the route parameter).
    $form['test_id']['#type'] = 'hidden';
    $form['test_id']['#value'] = $entity->getTestId();

    // Hide the original question_text field (we use our own).
    $form['question_text']['#access'] = FALSE;

    // Add question_text field manually (string_long type needs custom widget).
    $form['quiz_question_text'] = [
      '#type' => 'textarea',
      '#title' => $this->entity->getFieldDefinition('question_text')->getLabel(),
      '#default_value' => $entity->getQuestionText(),
      '#rows' => 3,
      '#required' => TRUE,
      '#weight' => -10,
    ];

    // Hide the base field widgets — we build custom ones.
    $form['answers']['#access'] = FALSE;
    $form['correct_answer']['#access'] = FALSE;

    // Get current answers: from form_state (after AJAX) or from entity.
    $answers = $form_state->get('quiz_answers');
    if ($answers === NULL) {
      $answers = $entity->getAnswers();
    }

    $correct_answer = $form_state->get('quiz_correct_answer');
    if ($correct_answer === NULL) {
      $correct_answer = $entity->getCorrectAnswer();
    }

    // Ensure at least 2 answer slots.
    while (count($answers) < 2) {
      $answers[] = '';
    }

    $form_state->set('quiz_answers', $answers);
    $form_state->set('quiz_correct_answer', $correct_answer);

    // --- Answers section (AJAX-powered) ---
    $num = count($answers);

    $form['answers_and_correct'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="quiz-answers-and-correct">',
      '#suffix' => '</div>',
    ];

    $form['answers_and_correct']['section_title'] = [
      '#markup' => '<h3>' . $this->t('Варианты ответов') . '</h3>',
    ];

    foreach ($answers as $delta => $answer) {
      $form['answers_and_correct'][$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['quiz-answer-row']],
      ];
      $form['answers_and_correct'][$delta]['answer'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Ответ @num', ['@num' => $delta + 1]),
        '#title_display' => 'before',
        '#default_value' => $answer,
        '#maxlength' => 512,
        '#required' => ($delta < 2),
      ];
      if ($num > 2) {
        $form['answers_and_correct'][$delta]['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Удалить'),
          '#name' => 'remove_answer_' . $delta,
          '#submit' => ['::removeAnswer'],
          '#ajax' => [
            'callback' => '::ajaxUpdateAnswers',
            'wrapper' => 'quiz-answers-and-correct',
          ],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--danger']],
        ];
      }
    }

    $form['answers_and_correct']['add_answer'] = [
      '#type' => 'submit',
      '#value' => $this->t('+ Добавить вариант'),
      '#submit' => ['::addAnswer'],
      '#ajax' => [
        'callback' => '::ajaxUpdateAnswers',
        'wrapper' => 'quiz-answers-and-correct',
      ],
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['button--primary']],
    ];

    // --- Correct answer select ---
    $correct_options = [];
    for ($i = 0; $i < $num; $i++) {
      $correct_options[$i] = $this->t('Ответ @num', ['@num' => $i + 1]);
    }
    $form['answers_and_correct']['correct_answer_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Верный ответ'),
      '#options' => $correct_options,
      '#default_value' => min($correct_answer, $num - 1),
      '#required' => TRUE,
      '#weight' => 2,
    ];

    // Move save/delete buttons to the bottom.
    $form['actions']['#weight'] = 100;
    if (isset($form['actions']['delete'])) {
      $form['actions']['delete']['#weight'] = 101;
    }

    $form['#entity_type'] = 'quiz_test_question';
    $form['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    return $form;
  }

  /**
   * AJAX callback: returns the rebuilt answers and correct answer section.
   */
  public function ajaxUpdateAnswers(array &$form, FormStateInterface $form_state) {
    return $form['answers_and_correct'];
  }

  /**
   * Submit handler: adds a new empty answer slot.
   */
  public function addAnswer(array &$form, FormStateInterface $form_state) {
    $answers = $this->collectCurrentAnswers($form_state);
    $answers[] = '';

    $correct = $this->collectCurrentCorrectAnswer($form_state);

    $form_state->set('quiz_answers', $answers);
    $form_state->set('quiz_correct_answer', $correct);
    $form_state->setRebuild();
  }

  /**
   * Submit handler: removes an answer at the given delta.
   */
  public function removeAnswer(array &$form, FormStateInterface $form_state) {
    $answers = $this->collectCurrentAnswers($form_state);
    $correct = $this->collectCurrentCorrectAnswer($form_state);

    // Extract delta from triggering element parents.
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    // Expected parents: ['answers_section', <delta>, 'remove']
    $delta = isset($parents[1]) ? (int) $parents[1] : -1;

    if ($delta >= 0 && $delta < count($answers)) {
      unset($answers[$delta]);
      $answers = array_values($answers);
    }

    // Adjust correct_answer if needed.
    if ($correct >= count($answers)) {
      $correct = max(0, count($answers) - 1);
    }
    if ($delta < $correct) {
      $correct--;
    }
    $correct = max(0, $correct);

    // Ensure at least 2 slots.
    while (count($answers) < 2) {
      $answers[] = '';
    }

    $form_state->set('quiz_answers', $answers);
    $form_state->set('quiz_correct_answer', $correct);
    $form_state->setRebuild();
  }

  /**
   * Collects current answers from form state or user input.
   */
  private function collectCurrentAnswers(FormStateInterface $form_state) {
    $stored = $form_state->get('quiz_answers');
    if ($stored !== NULL) {
      return $stored;
    }

    $input = $form_state->getUserInput();
    $section = $input['answers_section'] ?? [];

    $answers = [];
    foreach ($section as $key => $value) {
      if (is_numeric($key) && isset($value['answer'])) {
        $answers[] = $value['answer'];
      }
    }

    if (empty($answers)) {
      $answers = $this->entity->getAnswers();
    }

    while (count($answers) < 2) {
      $answers[] = '';
    }

    return $answers;
  }

  /**
   * Collects current correct_answer from form state or user input.
   */
  private function collectCurrentCorrectAnswer(FormStateInterface $form_state) {
    $stored = $form_state->get('quiz_correct_answer');
    if ($stored !== NULL) {
      return $stored;
    }

    $input = $form_state->getUserInput();
    if (isset($input['answers_and_correct']['correct_answer_select'])) {
      return (int) $input['answers_and_correct']['correct_answer_select'];
    }

    return $this->entity->getCorrectAnswer();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $test_id = $entity->getTestId();

    // Apply base-field widget values (weight, etc.) through the standard
    // entity-form mechanism. The overridden copyFormValuesToEntity() leaves
    // answers/correct_answer untouched (their widgets are hidden via
    // #access = FALSE and are restored afterwards); question_text/answers/
    // correct_answer are set manually below.
    $this->copyFormValuesToEntity($entity, $form, $form_state);

    if ($entity->isNew() && !empty($form_state->getValue('test_id'))) {
      $entity->set('test_id', $form_state->getValue('test_id'));
    }

    // Set question_text from our custom form element.
    $question_text = $form_state->getValue('quiz_question_text');
    if (is_string($question_text) && !empty(trim($question_text))) {
      $entity->setQuestionText(trim($question_text));
    }

    // Collect answers from the form.
    $section = $form_state->getValue('answers_and_correct');
    $answers = [];
    if (is_array($section)) {
      foreach ($section as $key => $value) {
        if (is_int($key) && !empty($value['answer'])) {
          $answers[] = trim($value['answer']);
        }
      }
    }

    if (count($answers) < 2) {
      $this->messenger()->addError($this->t('Необходимо минимум 2 варианта ответа.'));
      $form_state->setRebuild();
      return;
    }

    $entity->setAnswers($answers);

    $correct = (int) ($section['correct_answer_select'] ?? 0);
    if ($correct >= count($answers)) {
      $correct = 0;
    }
    $entity->setCorrectAnswer($correct);

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Вопрос создан.'));
        break;

      default:
        $this->messenger()->addMessage($this->t('Вопрос сохранён.'));
    }

    $form_state->setRedirect('quiz_test.question_list', ['quiz_test' => $test_id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Prevent parent from setting answers/correct_answer from base field
    // widgets since we handle them manually in submitForm().
    $answers_value = $entity->get('answers')->value;
    $correct_value = $entity->get('correct_answer')->value;

    parent::copyFormValuesToEntity($entity, $form, $form_state);

    // Restore original values — submitForm() will set them properly.
    $entity->set('answers', $answers_value);
    $entity->set('correct_answer', $correct_value);
  }

}
