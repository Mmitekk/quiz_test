<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quiz_test\Entity\QuizTest;

/**
 * Form for taking a quiz test.
 */
class TakeTestForm extends FormBase {

  /**
   * The quiz test entity.
   *
   * @var \Drupal\quiz_test\Entity\QuizTest
   */
  protected $quizTest;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_test_take_test_form';
  }

  /**
   * Sets the quiz test entity.
   */
  public function setQuizTest(QuizTest $quiz_test) {
    $this->quizTest = $quiz_test;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $questions = $this->quizTest->getQuestions();
    $open_questions = $this->quizTest->getOpenQuestions();

    if (empty($questions) && empty($open_questions)) {
      $form['no_questions'] = [
        '#markup' => '<div class="quiz-test-no-questions"><p>' . $this->t('В данном тесте пока нет вопросов. Пожалуйста, зайдите позже.') . '</p></div>',
      ];
      return $form;
    }

    $form['#attached']['library'][] = 'quiz_test/quiz_test.form';

    // Store test ID in form for validation.
    $form['test_id'] = [
      '#type' => 'hidden',
      '#value' => $this->quizTest->id(),
    ];

    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->quizTest->getTitle(),
      '#attributes' => ['class' => ['quiz-test-page-title']],
    ];

    $description = $this->quizTest->getDescription();
    if (!empty($description)) {
      $form['test_description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $description,
        '#attributes' => ['class' => ['quiz-test-description']],
      ];
    }

    $form['respondent_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quiz-test-respondent-info']],
    ];

    $form['respondent_info']['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ФИО'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'placeholder' => $this->t('Введите ваше полное имя'),
      ],
    ];

    // --- Part 1: multiple-choice questions. ---
    if (!empty($questions)) {
      $form['questions_intro'] = [
        '#markup' => '<p class="quiz-test-instructions">' . $this->t('Часть 1. Тестирование. Выберите один вариант ответа на каждый вопрос.') . '</p>',
      ];

      $form['questions'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => ['class' => ['quiz-test-questions']],
      ];

      foreach ($questions as $delta => $question) {
        $answers = $question->getAnswers();
        $options = [];
        foreach ($answers as $idx => $answer) {
          if (!empty($answer)) {
            $options[$idx] = ($idx + 1) . ') ' . $answer;
          }
        }

        $form['questions'][$question->id()] = [
          '#type' => 'radios',
          '#title' => ($delta + 1) . '. ' . $question->getQuestionText(),
          '#options' => $options,
          '#required' => TRUE,
          '#attributes' => [
            'class' => ['quiz-test-question'],
          ],
        ];
      }
    }

    // --- Part 2: open questions (optional). ---
    if (!empty($open_questions)) {
      $form['open_heading'] = [
        '#markup' => '<h3 class="quiz-test-open-section-title">' . $this->t('Часть 2. Открытые вопросы') . '</h3>',
      ];
      $form['open_intro'] = [
        '#markup' => '<p class="quiz-test-instructions">' . $this->t('Ответьте на открытые вопросы в свободной форме. Поля, обязательные для заполнения, помечены звёздочкой.') . '</p>',
      ];

      $form['open_questions'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => ['class' => ['quiz-test-open-questions']],
      ];

      foreach ($open_questions as $delta => $open_question) {
        $form['open_questions'][$open_question->id()] = [
          '#type' => 'textfield',
          '#title' => ($delta + 1) . '. ' . $open_question->getQuestionText(),
          '#required' => $open_question->isRequired(),
          '#maxlength' => 1000,
          '#attributes' => [
            'class' => ['quiz-test-open-question'],
            'placeholder' => $this->t('Ваш ответ'),
          ],
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Отправить ответы'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $full_name = $form_state->getValue('full_name');
    if (empty(trim($full_name))) {
      $form_state->setErrorByName('full_name', $this->t('Пожалуйста, введите ваше ФИО.'));
    }

    // Validate that every multiple-choice question is answered.
    $questions = $this->quizTest->getQuestions();
    if (!empty($questions)) {
      $answers = $form_state->getValue('questions') ?: [];
      $unanswered = [];
      foreach ($questions as $question) {
        $q_id = $question->id();
        if (!isset($answers[$q_id]) || $answers[$q_id] === '') {
          $unanswered[] = $question->id();
        }
      }

      if (!empty($unanswered)) {
        $count = count($unanswered);
        $form_state->setError($form['questions'], $this->t('Пожалуйста, ответьте на все вопросы теста. Не отвечено на @count вопрос(ов).', [
          '@count' => $count,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test = $this->quizTest;
    $questions = $test->getQuestions();
    $open_questions = $test->getOpenQuestions();
    $answers = $form_state->getValue('questions');
    $open_answers = $form_state->getValue('open_questions');

    // Score is calculated from multiple-choice questions only.
    $correct_count = 0;
    $total_count = count($questions);
    $results = [];

    foreach ($questions as $question) {
      $q_id = $question->id();
      $user_answer = isset($answers[$q_id]) ? (int) $answers[$q_id] : -1;
      $correct_answer = $question->getCorrectAnswer();
      $is_correct = ($user_answer === $correct_answer);

      if ($is_correct) {
        $correct_count++;
      }

      $results[] = [
        'question' => $question,
        'user_answer' => $user_answer,
        'correct_answer' => $correct_answer,
        'is_correct' => $is_correct,
      ];
    }

    $score_percent = $total_count > 0 ? round(($correct_count / $total_count) * 100) : NULL;

    // Collect open-question answers (free text).
    $open_results = [];
    foreach ($open_questions as $open_question) {
      $oq_id = $open_question->id();
      $user_text = isset($open_answers[$oq_id]) ? trim($open_answers[$oq_id]) : '';
      $open_results[] = [
        'question_text' => strip_tags($open_question->getQuestionText()),
        'user_answer' => $user_text,
      ];
    }

    // Send email notification with full results.
    $full_name = $form_state->getValue('full_name');
    $this->sendResultEmail($test, $results, $correct_count, $total_count, $score_percent, $full_name, $open_results);

    // Store results in tempstore — only need a flag, no detailed info shown.
    $tempstore = \Drupal::service('tempstore.private')->get('quiz_test');
    $tempstore->set('results_' . $test->id() . '_' . \Drupal::currentUser()->id(), [
      'correct' => $correct_count,
      'total' => $total_count,
      'score_percent' => $score_percent,
      'results' => $results,
      'open_results' => $open_results,
    ]);

    $form_state->setRedirect('quiz_test.take_test', ['quiz_test' => $test->id()]);
  }

  /**
   * Sends the test results email.
   */
  protected function sendResultEmail(QuizTest $test, array $results, $correct, $total, $score_percent, $full_name = '', array $open_results = []) {
    $config = \Drupal::config('quiz_test.settings');
    $user = \Drupal::currentUser();

    // Determine recipients.
    $recipients_string = $test->getEmailRecipients();
    if (empty($recipients_string)) {
      $recipients_string = $config->get('email_recipients');
    }
    $recipients = [];
    if (!empty($recipients_string)) {
      $recipients = array_map('trim', explode(',', $recipients_string));
      $recipients = array_filter($recipients);
    }

    if (empty($recipients)) {
      \Drupal::logger('quiz_test')->warning('Не настроены получатели email для теста «@test».', [
        '@test' => $test->label(),
      ]);
      return;
    }

    // Build email params.
    $question_details = [];
    foreach ($results as $result) {
      $q = $result['question'];
      $answers = $q->getAnswers();

      $user_answer_text = $result['user_answer'] >= 0 && isset($answers[$result['user_answer']])
        ? ($result['user_answer'] + 1) . ') ' . $answers[$result['user_answer']]
        : (string) $this->t('Нет ответа');
      $correct_answer_text = isset($answers[$result['correct_answer']])
        ? ($result['correct_answer'] + 1) . ') ' . $answers[$result['correct_answer']]
        : '';

      $question_details[] = [
        'question_text' => strip_tags($q->getQuestionText()),
        'user_answer' => (string) $user_answer_text,
        'correct_answer' => (string) $correct_answer_text,
        'is_correct' => $result['is_correct'],
      ];
    }

    // Determine subject.
    $subject = $test->getEmailSubject();
    if (empty($subject)) {
      $subject = $config->get('email_subject');
    }
    if (empty($subject)) {
      $subject = 'Результаты теста: @test_title — @user_name (@score)';
    }
    $score_token = ($score_percent !== NULL) ? ($score_percent . '%') : '—';
    $subject = str_replace('@test_title', $test->label(), $subject);
    $subject = str_replace('@user_name', $user->getDisplayName(), $subject);
    $subject = str_replace('@score', $score_token, $subject);

    $params = [
      'test' => $test,
      'user' => $user,
      'full_name' => $full_name,
      'correct' => $correct,
      'total' => $total,
      'score_percent' => $score_percent,
      'questions' => $question_details,
      'open_questions' => $open_results,
      'has_score' => ($total > 0),
      'subject' => $subject,
    ];

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    foreach ($recipients as $recipient) {
      $mail_manager = \Drupal::service('plugin.manager.mail');
      $mail_manager->mail('quiz_test', 'quiz_test_results', $recipient, $langcode, $params);
    }
  }

}
