<?php

namespace Drupal\quiz_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\quiz_test\Entity\QuizTest;
use Drupal\quiz_test\Entity\QuizTestQuestion;
use Drupal\quiz_test\Entity\QuizTestOpenQuestion;
use Drupal\quiz_test\Form\TakeTestForm;

/**
 * Controller for Quiz Test module.
 */
class QuizTestController extends ControllerBase {

  /**
   * Displays a list of questions for a test.
   */
  public function questionList(QuizTest $quiz_test) {
    $questions = $quiz_test->getQuestions();

    $header = [
      ['data' => $this->t('#'), '#width' => '40'],
      ['data' => $this->t('Вопрос'), '#width' => '40%'],
      ['data' => $this->t('Вариантов')],
      ['data' => $this->t('Верный ответ')],
      ['data' => $this->t('Вес'), '#width' => '70'],
      ['data' => $this->t('Операции')],
    ];

    $rows = [];
    foreach ($questions as $delta => $question) {
      $answers = $question->getAnswers();
      $correct = $question->getCorrectAnswer();
      $non_empty = array_filter($answers, function ($a) {
        return $a !== '' && $a !== NULL;
      });
      $count = count($non_empty);

      $edit_url = Url::fromRoute('quiz_test.question_edit', [
        'quiz_test' => $quiz_test->id(),
        'quiz_test_question' => $question->id(),
      ]);
      $delete_url = Url::fromRoute('quiz_test.question_delete', [
        'quiz_test' => $quiz_test->id(),
        'quiz_test_question' => $question->id(),
      ]);

      $correct_text = isset($non_empty[$correct])
        ? ($correct + 1) . ') ' . $non_empty[$correct]
        : '-';

      $rows[] = [
        ['data' => $delta + 1],
        ['data' => $question->getQuestionText()],
        ['data' => $count],
        ['data' => $correct_text],
        ['data' => $question->getWeight()],
        ['data' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Редактировать'),
              'url' => $edit_url,
            ],
            'delete' => [
              'title' => $this->t('Удалить'),
              'url' => $delete_url,
            ],
          ],
        ]],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Вопросы ещё не добавлены.'),
      '#attributes' => ['class' => ['quiz-test-admin-table']],
    ];

    $build['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    return $build;
  }

  /**
   * Displays the form to add a new question.
   */
  public function questionAdd(QuizTest $quiz_test) {
    $question = \Drupal::entityTypeManager()
      ->getStorage('quiz_test_question')
      ->create(['test_id' => $quiz_test->id()]);

    $form = \Drupal::entityTypeManager()
      ->getFormObject('quiz_test_question', 'add')
      ->setEntity($question);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Displays the form to edit a question.
   */
  public function questionEdit(QuizTest $quiz_test, QuizTestQuestion $quiz_test_question) {
    $form = \Drupal::entityTypeManager()
      ->getFormObject('quiz_test_question', 'edit')
      ->setEntity($quiz_test_question);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Displays the delete confirmation form for a question.
   */
  public function questionDelete(QuizTest $quiz_test, QuizTestQuestion $quiz_test_question) {
    $form = \Drupal::entityTypeManager()
      ->getFormObject('quiz_test_question', 'delete')
      ->setEntity($quiz_test_question);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Displays a list of open questions for a test.
   */
  public function openQuestionList(QuizTest $quiz_test) {
    $questions = $quiz_test->getOpenQuestions();

    $header = [
      ['data' => $this->t('#'), '#width' => '40'],
      ['data' => $this->t('Вопрос'), '#width' => '60%'],
      ['data' => $this->t('Обязательно'), '#width' => '120'],
      ['data' => $this->t('Вес'), '#width' => '70'],
      ['data' => $this->t('Операции')],
    ];

    $rows = [];
    foreach ($questions as $delta => $question) {
      $edit_url = Url::fromRoute('quiz_test.open_question_edit', [
        'quiz_test' => $quiz_test->id(),
        'quiz_test_open_question' => $question->id(),
      ]);
      $delete_url = Url::fromRoute('quiz_test.open_question_delete', [
        'quiz_test' => $quiz_test->id(),
        'quiz_test_open_question' => $question->id(),
      ]);

      $rows[] = [
        ['data' => $delta + 1],
        ['data' => $question->getQuestionText()],
        ['data' => $question->isRequired() ? $this->t('Да') : $this->t('Нет')],
        ['data' => $question->getWeight()],
        ['data' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Редактировать'),
              'url' => $edit_url,
            ],
            'delete' => [
              'title' => $this->t('Удалить'),
              'url' => $delete_url,
            ],
          ],
        ]],
      ];
    }

    $build['intro'] = [
      '#markup' => '<p>' . $this->t('Открытые вопросы — вторая, опциональная часть теста. Пользователь вписывает ответ в свободной форме. Открытая часть появляется в форме прохождения и в письме с результатами только если у теста есть хотя бы один открытый вопрос. Открытые ответы не влияют на процентный балл.') . '</p>',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Открытые вопросы ещё не добавлены. Открытая часть будет отключена, пока не добавлен хотя бы один вопрос.'),
      '#attributes' => ['class' => ['quiz-test-admin-table']],
    ];

    $build['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    return $build;
  }

  /**
   * Displays the form to add a new open question.
   */
  public function openQuestionAdd(QuizTest $quiz_test) {
    $question = \Drupal::entityTypeManager()
      ->getStorage('quiz_test_open_question')
      ->create(['test_id' => $quiz_test->id()]);

    $form = \Drupal::entityTypeManager()
      ->getFormObject('quiz_test_open_question', 'add')
      ->setEntity($question);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Displays the form to edit an open question.
   */
  public function openQuestionEdit(QuizTest $quiz_test, QuizTestOpenQuestion $quiz_test_open_question) {
    $form = \Drupal::entityTypeManager()
      ->getFormObject('quiz_test_open_question', 'edit')
      ->setEntity($quiz_test_open_question);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Displays the delete confirmation form for an open question.
   */
  public function openQuestionDelete(QuizTest $quiz_test, QuizTestOpenQuestion $quiz_test_open_question) {
    $form = \Drupal::entityTypeManager()
      ->getFormObject('quiz_test_open_question', 'delete')
      ->setEntity($quiz_test_open_question);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Displays the test page for taking a test.
   */
  public function takeTest(QuizTest $quiz_test) {
    // Check if there are stored results.
    $tempstore = \Drupal::service('tempstore.private')->get('quiz_test');
    $result_key = 'results_' . $quiz_test->id() . '_' . \Drupal::currentUser()->id();
    $results = $tempstore->get($result_key);

    if ($results) {
      // Show results page (without detailed correct/incorrect info).
      $tempstore->delete($result_key);
      return $this->buildResultsPage($quiz_test, $results);
    }

    // Show the test form.
    /** @var \Drupal\quiz_test\Form\TakeTestForm $form */
    $take_test_form = \Drupal::classResolver(TakeTestForm::class);
    $take_test_form->setQuizTest($quiz_test);

    return \Drupal::formBuilder()->getForm($take_test_form);
  }

  /**
   * Builds the results page render array.
   *
   * Shows only a thank-you message — no correct/incorrect details.
   */
  protected function buildResultsPage(QuizTest $quiz_test, array $results) {
    $build['#attached']['library'][] = 'quiz_test/quiz_test.form';

    $build['summary'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['result-summary'],
      ],
      'icon' => [
        '#markup' => '<div class="result-icon"></div>',
      ],
      'text' => [
        '#markup' => '<p>' . $this->t('Благодарим вас за прохождение теста «@title»! Ваши ответы были успешно отправлены.', [
          '@title' => trim($quiz_test->getTitle()),
        ]) . '</p>',
      ],
    ];

    return $build;
  }

  /**
   * Title callback for the take test page.
   */
  public function testTitle(QuizTest $quiz_test) {
    return $quiz_test->getTitle();
  }

  /**
   * Access callback for taking a test.
   */
  public function accessTakeTest(QuizTest $quiz_test) {
    $account = \Drupal::currentUser();
    // Administrators always have access.
    if ($account->hasPermission('administer quiz tests')) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }
    // Check if user has the configured role.
    $config = \Drupal::config('quiz_test.settings');
    $role_id = $config->get('access_role');
    if (!empty($role_id) && in_array($role_id, $account->getRoles())) {
      return \Drupal\Core\Access\AccessResult::allowedIf($quiz_test->isPublished())
        ->addCacheableDependency($quiz_test);
    }
    return \Drupal\Core\Access\AccessResult::forbidden('У вас нет прав для прохождения этого теста.');
  }

}
