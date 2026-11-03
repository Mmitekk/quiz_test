<?php

namespace Drupal\quiz_test\Service;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\quiz_test\Entity\QuizTestResult;

/**
 * Service for generating, rendering, and sending daily test reports.
 */
class QuizTestDailyReportService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a QuizTestDailyReportService object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    MailManagerInterface $mail_manager,
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->logger = $logger_factory->get('quiz_test');
  }

  /**
   * Gets the immutable daily report configuration.
   */
  public function getConfig() {
    return $this->configFactory->get('quiz_test.daily_report.settings');
  }

  /**
   * Gets the editable daily report configuration.
   */
  public function getEditableConfig() {
    return $this->configFactory->getEditable('quiz_test.daily_report.settings');
  }

  /**
   * Gets or generates the secret access token for the web view link.
   */
  public function getAccessToken() {
    $config = $this->getEditableConfig();
    $token = $config->get('access_token');
    if (empty($token)) {
      $token = Crypt::randomBytesBase64(24);
      $config->set('access_token', $token)->save();
    }
    return $token;
  }

  /**
   * Generates the absolute web URL for viewing the daily report.
   */
  public function getWebReportUrl($date_string) {
    try {
      return Url::fromRoute(
        'quiz_test.daily_report_view',
        ['date' => $date_string],
        [
          'query' => ['token' => $this->getAccessToken()],
          'absolute' => TRUE,
        ]
      )->toString();
    }
    catch (\Exception $e) {
      return '';
    }
  }

  /**
   * Loads all QuizTestResult entities for a given date in the site timezone.
   *
   * @param \DateTimeInterface $date
   *   The date to inspect.
   *
   * @return \Drupal\quiz_test\Entity\QuizTestResult[]
   *   Array of results submitted on that date.
   */
  public function getResultsForDate(\DateTimeInterface $date) {
    $timezone = $this->getTimezone();
    $date_formatted = $date->format('Y-m-d');

    try {
      $start = new \DateTime($date_formatted . ' 00:00:00', new \DateTimeZone($timezone));
      $end = new \DateTime($date_formatted . ' 23:59:59', new \DateTimeZone($timezone));
    }
    catch (\Exception $e) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('quiz_test_result');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('created', $start->getTimestamp(), '>=')
      ->condition('created', $end->getTimestamp(), '<=')
      ->sort('created', 'ASC');

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\quiz_test\Entity\QuizTestResult[] $results */
    $results = $storage->loadMultiple($ids);
    return $results;
  }

  /**
   * Builds the structured data array for the daily report.
   *
   * @param \DateTimeInterface $date
   *   The date of the report.
   *
   * @return array
   *   Report data array ready for rendering or mailing.
   */
  public function buildReportData(\DateTimeInterface $date) {
    $date_raw = $date->format('Y-m-d');
    $results = $this->getResultsForDate($date);

    $submissions = [];
    $unique_names = [];
    $total_score_sum = 0;
    $scored_tests_count = 0;
    $tests_summary = [];

    foreach ($results as $result) {
      $owner = $result->getOwner();
      $user_display = ($owner && $owner->id() > 0) ? $owner->getDisplayName() : 'Анонимный';
      $user_email = ($owner && $owner->id() > 0) ? $owner->getEmail() : '';

      $full_name = trim((string) $result->getFullName());
      if (!empty($full_name)) {
        $unique_names[$full_name] = TRUE;
      }
      else {
        $unique_names[$user_display] = TRUE;
      }

      $test_title = $result->getTestTitle();
      if (!isset($tests_summary[$test_title])) {
        $tests_summary[$test_title] = 0;
      }
      $tests_summary[$test_title]++;

      if ($result->hasScore()) {
        $total_score_sum += $result->getScorePercent();
        $scored_tests_count++;
      }

      // Process answers data snapshot.
      $answer_data = $result->getAnswerData();
      $choice_questions = [];
      $open_questions = [];

      foreach ($answer_data as $ans) {
        $type = $ans['type'] ?? 'choice';
        if ($type === 'open') {
          $open_questions[] = [
            'question' => $ans['question'] ?? '',
            'user_answer' => $ans['user_answer_text'] ?? '',
          ];
        }
        else {
          $choice_questions[] = [
            'question' => $ans['question'] ?? '',
            'user_answer' => $ans['user_answer_text'] ?? '',
            'correct_answer' => $ans['correct_answer_text'] ?? '',
            'is_correct' => !empty($ans['is_correct']),
          ];
        }
      }

      $submissions[] = [
        'id' => $result->id(),
        'created_time' => $this->dateFormatter->format($result->getCreatedTime(), 'custom', 'H:i:s'),
        'created_timestamp' => $result->getCreatedTime(),
        'test_title' => $test_title,
        'test_id' => $result->getTestId(),
        'full_name' => $full_name,
        'user_display_name' => $user_display,
        'user_email' => $user_email,
        'correct' => $result->getCorrectCount(),
        'total' => $result->getTotalCount(),
        'score_percent' => $result->getScorePercent(),
        'has_score' => $result->hasScore(),
        'choice_questions' => $choice_questions,
        'open_questions' => $open_questions,
      ];
    }

    $avg_score = $scored_tests_count > 0 ? round($total_score_sum / $scored_tests_count) : NULL;

    return [
      'date_raw' => $date_raw,
      'date_formatted' => $this->dateFormatter->format($date->getTimestamp(), 'custom', 'd.m.Y'),
      'date_long' => $this->dateFormatter->format($date->getTimestamp(), 'custom', 'j F Y'),
      'total_submissions' => count($submissions),
      'unique_respondents_count' => count($unique_names),
      'average_score' => $avg_score,
      'tests_summary' => $tests_summary,
      'submissions' => $submissions,
      'web_view_url' => $this->getWebReportUrl($date_raw),
    ];
  }

  /**
   * Renders the daily report HTML.
   */
  public function renderHtmlReport(array $report_data, $include_details = TRUE) {
    $render_array = [
      '#theme' => 'quiz_test_daily_report_email',
      '#report' => $report_data,
      '#include_details' => $include_details,
      '#site_name' => $this->configFactory->get('system.site')->get('name'),
    ];

    return (string) $this->renderer->renderPlain($render_array);
  }

  /**
   * Sends the daily report by email.
   *
   * @param \DateTimeInterface|null $date
   *   The date to report on (defaults to today).
   * @param array $override_recipients
   *   Optional recipient emails (overrides config).
   * @param bool $force
   *   Whether to force sending even if disabled or empty.
   *
   * @return array
   *   Status array:
   *   - success: bool
   *   - sent: bool
   *   - count: int
   *   - recipients: array
   *   - message: string
   */
  public function sendDailyReport(\DateTimeInterface $date = NULL, array $override_recipients = [], $force = FALSE) {
    $timezone = $this->getTimezone();
    $date = $date ?: new \DateTime('now', new \DateTimeZone($timezone));
    $config = $this->getConfig();

    if (!$force && !$config->get('enabled')) {
      return [
        'success' => FALSE,
        'sent' => FALSE,
        'count' => 0,
        'recipients' => [],
        'message' => 'Автоматическая отправка ежедневного отчёта отключена в настройках.',
      ];
    }

    // Determine recipients.
    if (!empty($override_recipients)) {
      $recipients = $override_recipients;
    }
    else {
      $raw_recipients = (string) $config->get('recipients');
      $recipients = preg_split('/[\r\n,]+/', $raw_recipients);
    }

    $recipients = array_map('trim', $recipients);
    $recipients = array_filter($recipients, function ($email) {
      return \Drupal::service('email.validator')->isValid($email);
    });
    $recipients = array_unique($recipients);

    if (empty($recipients)) {
      return [
        'success' => FALSE,
        'sent' => FALSE,
        'count' => 0,
        'recipients' => [],
        'message' => 'Не указаны корректные адреса электронной почты получателей.',
      ];
    }

    $report_data = $this->buildReportData($date);
    $has_submissions = !empty($report_data['submissions']);

    // Check if sending empty reports is allowed.
    if (!$has_submissions && !$force && !$config->get('send_empty')) {
      // Mark as processed today so cron doesn't repeat checks.
      $this->getEditableConfig()->set('last_sent_date', $date->format('Y-m-d'))->save();
      return [
        'success' => TRUE,
        'sent' => FALSE,
        'count' => 0,
        'recipients' => $recipients,
        'message' => 'За указанную дату тесты не заполнялись. Отправка отчёта пропущена согласно настройкам.',
      ];
    }

    // Format subject.
    $subject_tpl = $config->get('subject') ?: 'Ежедневный отчёт по тестированию за @date — заполнено тестов: @count';
    $subject = str_replace(
      ['@date', '@count', '@site_name'],
      [$report_data['date_formatted'], $report_data['total_submissions'], $this->configFactory->get('system.site')->get('name')],
      $subject_tpl
    );

    $params = [
      'report' => $report_data,
      'subject' => $subject,
      'include_details' => $config->get('include_details') !== NULL ? (bool) $config->get('include_details') : TRUE,
      'site_name' => $this->configFactory->get('system.site')->get('name'),
    ];

    $langcode = $this->configFactory->get('system.site')->get('langcode') ?: 'ru';
    $sent_count = 0;
    $failed_recipients = [];

    foreach ($recipients as $recipient) {
      $mail_result = $this->mailManager->mail('quiz_test', 'daily_report', $recipient, $langcode, $params);
      if (!empty($mail_result['result'])) {
        $sent_count++;
      }
      else {
        $failed_recipients[] = $recipient;
      }
    }

    // Update last sent date.
    $this->getEditableConfig()
      ->set('last_sent_date', $date->format('Y-m-d'))
      ->save();

    if ($sent_count > 0) {
      $this->logger->notice('Ежедневный отчёт по тестированию за @date успешно отправлен на @count адрес(ов). Заполнено тестов: @subs.', [
        '@date' => $report_data['date_formatted'],
        '@count' => $sent_count,
        '@subs' => $report_data['total_submissions'],
      ]);
    }

    if (!empty($failed_recipients)) {
      $this->logger->warning('Не удалось доставить ежедневный отчёт на следующие адреса: @list', [
        '@list' => implode(', ', $failed_recipients),
      ]);
    }

    return [
      'success' => $sent_count > 0,
      'sent' => $sent_count > 0,
      'count' => $report_data['total_submissions'],
      'recipients' => $recipients,
      'message' => $sent_count > 0
        ? 'Отчёт успешно отправлен на ' . $sent_count . ' адрес(ов).'
        : 'Произошла ошибка при отправке писем.',
    ];
  }

  /**
   * Helper to get the site timezone string.
   */
  protected function getTimezone() {
    return $this->configFactory->get('system.date')->get('timezone.default') ?: date_default_timezone_get();
  }

}
