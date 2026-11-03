<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter form for the admin results list (by test and by full name).
 */
class QuizTestResultFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_test_result_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $test_id = '', $name = '') {
    // List all tests (published or not) as filter options.
    $tests = $this->entityTypeManager()->getStorage('quiz_test')->loadMultiple();
    $options = ['' => $this->t('— Любой тест —')];
    foreach ($tests as $test) {
      $options[$test->id()] = $test->label();
    }

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Фильтр'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['quiz-test-results-filter']],
    ];

    $form['filters']['test_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Тест'),
      '#options' => $options,
      '#default_value' => (string) $test_id,
    ];

    $form['filters']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ФИО содержит'),
      '#default_value' => (string) $name,
      '#maxlength' => 255,
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Применить'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];
    $test_id = (string) $form_state->getValue('test_id');
    $name = trim((string) $form_state->getValue('name'));
    if ($test_id !== '') {
      $query['test_id'] = $test_id;
    }
    if ($name !== '') {
      $query['name'] = $name;
    }

    $form_state->setRedirect('quiz_test.results', [], ['query' => $query]);
  }

}
