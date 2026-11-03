<?php

namespace Drupal\quiz_test\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\quiz_test\Entity\QuizTestResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the admin results log (who passed which test and when).
 */
class QuizTestResultController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * Displays the list of test submissions.
   */
  public function resultList(Request $request) {
    $test_id = $request->query->get('test_id') ?: '';
    $name = trim((string) $request->query->get('name'));

    $build['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    // Filter form.
    $build['filter'] = $this->formBuilder()->getForm(
      'Drupal\quiz_test\Form\QuizTestResultFilterForm',
      $test_id,
      $name
    );

    $header = [
      ['data' => $this->t('Дата')],
      ['data' => $this->t('Пользователь')],
      ['data' => $this->t('ФИО')],
      ['data' => $this->t('Тест')],
      ['data' => $this->t('Балл')],
      ['data' => $this->t('Операции')],
    ];

    $storage = $this->entityTypeManager->getStorage('quiz_test_result');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    if ($test_id !== '' && $test_id !== NULL) {
      $query->condition('test_id', (int) $test_id);
    }
    if ($name !== '') {
      $query->condition('full_name', '%' . $name . '%', 'LIKE');
    }

    $ids = $query->pager(50)->execute();

    $rows = [];
    if (!empty($ids)) {
      /** @var \Drupal\quiz_test\Entity\QuizTestResult[] $results */
      $results = $storage->loadMultiple($ids);
      foreach ($results as $result) {
        $rows[] = $this->buildListRow($result);
      }
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => ($test_id !== '' || $name !== '')
        ? $this->t('По заданным фильтрам ничего не найдено.')
        : $this->t('Пока никто не проходил тесты.'),
      '#attributes' => ['class' => ['quiz-test-admin-table', 'quiz-test-results-table']],
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Builds a single table row for the results list.
   */
  protected function buildListRow(QuizTestResult $result) {
    // Date.
    $date = $this->dateFormatter->format($result->getCreatedTime(), 'medium');

    // Drupal user (site account).
    $owner = $result->getOwner();
    if ($owner && $owner->id() > 0) {
      $user_cell = $owner->toLink($owner->getDisplayName(), 'canonical')->toRenderable();
    }
    else {
      $user_cell = $this->t('Анонимный');
    }

    // Test title (snapshot, linked to the test when it still exists).
    $test = $result->getTest();
    if ($test) {
      // Link text is escaped by the link renderer itself — no manual escape.
      $test_cell = Link::fromTextAndUrl($result->getTestTitle(), Url::fromRoute('entity.quiz_test.edit_form', ['quiz_test' => $result->getTestId()]))->toRenderable();
    }
    else {
      $test_cell = Html::escape($result->getTestTitle());
    }

    // Score.
    if ($result->hasScore()) {
      $class = $result->getScorePercent() >= 50 ? 'quiz-test-score-pass' : 'quiz-test-score-fail';
      $score_cell = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $result->getCorrectCount() . ' / ' . $result->getTotalCount() . ' (' . $result->getScorePercent() . '%)',
        '#attributes' => ['class' => [$class]],
      ];
    }
    else {
      $score_cell = '—';
    }

    $view_url = Url::fromRoute('quiz_test.result_view', ['quiz_test_result' => $result->id()]);
    $delete_url = Url::fromRoute('entity.quiz_test_result.delete_form', ['quiz_test_result' => $result->id()]);

    return [
      ['data' => $date],
      ['data' => $user_cell],
      ['data' => Html::escape($result->getFullName())],
      ['data' => $test_cell],
      ['data' => $score_cell],
      ['data' => [
        '#type' => 'operations',
        '#links' => [
          'view' => [
            'title' => $this->t('Результаты'),
            'url' => $view_url,
          ],
          'delete' => [
            'title' => $this->t('Удалить'),
            'url' => $delete_url,
          ],
        ],
      ]],
    ];
  }

  /**
   * Displays a single submission: answers per question, like the email.
   */
  public function resultView(QuizTestResult $quiz_test_result) {
    $build['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    // --- Summary. ---
    $owner = $quiz_test_result->getOwner();
    $user_label = ($owner && $owner->id() > 0)
      ? $owner->getDisplayName() . ' [uid ' . $owner->id() . ']'
      : $this->t('Анонимный');

    if ($quiz_test_result->hasScore()) {
      $class = $quiz_test_result->getScorePercent() >= 50 ? 'quiz-test-score-pass' : 'quiz-test-score-fail';
      $score = $this->t('@correct из @total (@score%)', [
        '@correct' => $quiz_test_result->getCorrectCount(),
        '@total' => $quiz_test_result->getTotalCount(),
        '@score' => $quiz_test_result->getScorePercent(),
      ]);
    }
    else {
      $class = 'quiz-test-result-neutral';
      $score = '—';
    }

    $build['summary'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['quiz-test-result-summary']],
      'items' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [
          $this->t('Тест: @test', ['@test' => $quiz_test_result->getTestTitle()]),
          $this->t('Пользователь: @user', ['@user' => $user_label]),
          $this->t('ФИО: @name', ['@name' => $quiz_test_result->getFullName()]),
          $this->t('Дата: @date', ['@date' => $this->dateFormatter->format($quiz_test_result->getCreatedTime(), 'long')]),
          [
            '#markup' => $this->t('Балл: @score', ['@score' => $score]),
            '#wrapper_attributes' => ['class' => [$class]],
          ],
        ],
      ],
    ];

    // --- Answers. ---
    $header = [
      ['data' => $this->t('#'), '#width' => '40'],
      ['data' => $this->t('Вопрос'), '#width' => '40%'],
      ['data' => $this->t('Ответ пользователя')],
      ['data' => $this->t('Верный ответ')],
      ['data' => $this->t('Итог'), '#width' => '70'],
    ];

    $rows = [];
    foreach ($quiz_test_result->getAnswerData() as $delta => $item) {
      $is_open = (($item['type'] ?? 'choice') === 'open');

      if ($is_open) {
        $outcome = ['data' => '—', 'class' => 'quiz-test-result-neutral'];
        $correct_cell = '—';
      }
      else {
        $correct_cell = $item['correct_answer_text'] ?? '—';
        $outcome = !empty($item['is_correct'])
          ? ['data' => '✓', 'class' => 'quiz-test-result-correct']
          : ['data' => '✗', 'class' => 'quiz-test-result-wrong'];
      }

      $rows[] = [
        ['data' => $delta + 1],
        ['data' => Html::escape($item['question'] ?? '')],
        ['data' => Html::escape($item['user_answer_text'] ?? '')],
        ['data' => Html::escape($correct_cell)],
        $outcome,
      ];
    }

    $build['answers'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Снимок ответов отсутствует.'),
      '#attributes' => ['class' => ['quiz-test-admin-table']],
    ];

    $build['back'] = [
      '#type' => 'link',
      '#title' => $this->t('← Все результаты'),
      '#url' => Url::fromRoute('quiz_test.results'),
      '#attributes' => ['class' => ['quiz-test-result-back']],
    ];

    return $build;
  }

  /**
   * Title callback for the single result page.
   */
  public function resultTitle(QuizTestResult $quiz_test_result) {
    return $this->t('Результат: @name', ['@name' => $quiz_test_result->getFullName()]);
  }

}
