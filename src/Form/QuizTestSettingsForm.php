<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for Quiz Test module.
 */
class QuizTestSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quiz_test.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_test_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quiz_test.settings');

    // Get all roles.
    $roles = user_role_names(TRUE);
    // Remove the administrator role from the list (they always have access).
    unset($roles['administrator']);

    $form['access_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Роль для доступа к тестам'),
      '#description' => $this->t('Выберите роль, которой будет разрешено проходить тесты. Администраторы всегда имеют полный доступ.'),
      '#options' => ['' => $this->t('-- Выберите роль --')] + $roles,
      '#default_value' => $config->get('access_role'),
      '#required' => TRUE,
    ];

    $form['email_recipients'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Получатели email по умолчанию'),
      '#description' => $this->t('Введите адреса электронной почты через запятую. На эти адреса будут отправляться результаты тестирования. Для каждого теста можно указать отдельных получателей.'),
      '#default_value' => $config->get('email_recipients'),
      '#rows' => 3,
    ];

    $form['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Тема email по умолчанию'),
      '#description' => $this->t('Тема письма с результатами по умолчанию. Можно переопределить для каждого теста. Доступные подстановки: @test_title, @user_name, @score.'),
      '#default_value' => $config->get('email_subject'),
      '#maxlength' => 255,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('quiz_test.settings')
      ->set('access_role', $form_state->getValue('access_role'))
      ->set('email_recipients', $form_state->getValue('email_recipients'))
      ->set('email_subject', $form_state->getValue('email_subject'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
