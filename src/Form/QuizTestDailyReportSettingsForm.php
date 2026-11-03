<?php

namespace Drupal\quiz_test\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\quiz_test\Service\QuizTestDailyReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure daily summary report settings for the Quiz Test module.
 */
class QuizTestDailyReportSettingsForm extends ConfigFormBase {

  /**
   * The daily report service.
   *
   * @var \Drupal\quiz_test\Service\QuizTestDailyReportService
   */
  protected $dailyReportService;

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
    $instance->dailyReportService = $container->get('quiz_test.daily_report');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quiz_test.daily_report.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_test_daily_report_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quiz_test.daily_report.settings');

    $form['#attached']['library'][] = 'quiz_test/quiz_test.admin';

    // Info banner.
    $form['intro'] = [
      '#markup' => '<p>' . $this->t('В этом разделе настраивается автоматическая рассылка сводного ежедневного отчёта. В конце дня на указанные адреса электронной почты отправляется красивый HTML-отчёт (и ссылка на него), в котором видно, какие тесты были заполнены, кто их заполнил (ФИО и логин) и с какими ответами.') . '</p>',
    ];

    // Status box.
    $last_sent = $config->get('last_sent_date') ?: $this->t('Ещё не отправлялся');
    $timezone = $this->config('system.date')->get('timezone.default') ?: date_default_timezone_get();
    $now_formatted = $this->dateFormatter->format(\Drupal::time()->getRequestTime(), 'custom', 'd.m.Y H:i:s') . ' (' . $timezone . ')';
    $today_str = (new \DateTime('now', new \DateTimeZone($timezone)))->format('Y-m-d');
    $today_preview_url = Url::fromRoute('quiz_test.daily_report_view', ['date' => $today_str], [
      'query' => ['token' => $this->dailyReportService->getAccessToken()],
      'attributes' => ['target' => '_blank'],
    ]);

    $form['status_box'] = [
      '#type' => 'details',
      '#title' => $this->t('Информация о статусе и предпросмотр'),
      '#open' => TRUE,
    ];
    $form['status_box']['info'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Текущее время на сервере: <strong>@time</strong>', ['@time' => $now_formatted]),
        $this->t('Дата последней автоматической отправки: <strong>@date</strong>', ['@date' => $last_sent]),
        Link::fromTextAndUrl($this->t('Открыть веб-версию отчёта за сегодня в новой вкладке &rarr;'), $today_preview_url)->toRenderable(),
      ],
    ];

    // --- Delivery settings ---
    $form['delivery'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Параметры доставки и получатели'),
    ];

    $form['delivery']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Включить автоматическую отправку ежедневного отчёта'),
      '#description' => $this->t('Отчёт будет формироваться и отправляться в конце дня при выполнении планировщика Cron.'),
      '#default_value' => $config->get('enabled') ?? TRUE,
    ];

    $form['delivery']['recipients'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Адреса электронной почты получателей'),
      '#description' => $this->t('Укажите email-адреса, на которые должен приходить отчёт. Можно разделять запятыми или указывать каждый адрес с новой строки.'),
      '#default_value' => $config->get('recipients') ?: '',
      '#rows' => 3,
      '#required' => FALSE,
    ];

    $form['delivery']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Шаблон темы письма'),
      '#description' => $this->t('Доступные подстановки: <code>@date</code> (дата), <code>@count</code> (количество заполненных тестов), <code>@site_name</code> (название сайта).'),
      '#default_value' => $config->get('subject') ?: 'Ежедневный отчёт по тестированию за @date — заполнено тестов: @count',
      '#maxlength' => 255,
    ];

    // --- Schedule settings ---
    $form['schedule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Расписание отправки (время окончания дня)'),
    ];

    $hour_options = [];
    for ($h = 0; $h <= 23; $h++) {
      $hour_str = sprintf('%02d:00', $h);
      if ($h === 20) {
        $hour_options[$h] = $hour_str . ' (' . $this->t('по умолчанию') . ')';
      }
      elseif ($h === 18) {
        $hour_options[$h] = $hour_str . ' (' . $this->t('конец рабочего дня') . ')';
      }
      else {
        $hour_options[$h] = $hour_str;
      }
    }

    $form['schedule']['send_hour'] = [
      '#type' => 'select',
      '#title' => $this->t('Время отправки отчёта'),
      '#description' => $this->t('Отчёт будет отправлен при первом запуске Cron после наступления выбранного часа по времени сервера.'),
      '#options' => $hour_options,
      '#default_value' => $config->get('send_hour') !== NULL ? (int) $config->get('send_hour') : 20,
    ];

    $form['schedule']['weekdays_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Отправлять только по рабочим дням (понедельник &mdash; пятница)'),
      '#description' => $this->t('Если отмечено, в субботу и воскресенье отчёты отправляться не будут.'),
      '#default_value' => (bool) $config->get('weekdays_only'),
    ];

    // --- Content options ---
    $form['content_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Содержание отчёта'),
    ];

    $form['content_options']['include_details'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Включать детальные ответы на вопросы в письмо'),
      '#description' => $this->t('Если включено, в письме будет отображаться полный список вопросов с выбранными ответами и статусом «Верно/Неверно», а также ответы на открытые вопросы. Если выключено — письмо будет содержать только сводную таблицу участников и ссылку на сайт.'),
      '#default_value' => $config->get('include_details') !== NULL ? (bool) $config->get('include_details') : TRUE,
    ];

    $form['content_options']['send_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Отправлять отчёт, даже если за день тесты никто не проходил'),
      '#description' => $this->t('Если не отмечено, в дни, когда никто не заполнял тесты, пустые письма отправляться не будут.'),
      '#default_value' => (bool) $config->get('send_empty'),
    ];

    // --- Actions ---
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Сохранить настройки'),
      '#button_type' => 'primary',
    ];

    $form['actions']['send_now'] = [
      '#type' => 'submit',
      '#value' => $this->t('Отправить отчёт за сегодня сейчас'),
      '#submit' => ['::sendNow'],
      '#button_type' => 'secondary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $raw_recipients = (string) $form_state->getValue('recipients');
    if (!empty(trim($raw_recipients))) {
      $emails = preg_split('/[\r\n,]+/', $raw_recipients);
      $invalid = [];
      foreach ($emails as $email) {
        $email = trim($email);
        if (!empty($email) && !\Drupal::service('email.validator')->isValid($email)) {
          $invalid[] = $email;
        }
      }
      if (!empty($invalid)) {
        $form_state->setErrorByName('recipients', $this->t('Следующие адреса некорректны: @list', [
          '@list' => implode(', ', $invalid),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->saveConfigValues($form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submit handler for sending the report immediately.
   */
  public function sendNow(array &$form, FormStateInterface $form_state) {
    // Save current values first.
    $this->saveConfigValues($form_state);

    $raw_recipients = (string) $form_state->getValue('recipients');
    $emails = preg_split('/[\r\n,]+/', $raw_recipients);
    $emails = array_map('trim', $emails);
    $emails = array_filter($emails, function ($email) {
      return \Drupal::service('email.validator')->isValid($email);
    });

    if (empty($emails)) {
      $this->messenger()->addError($this->t('Для отправки отчёта необходимо указать хотя бы один корректный email получателя.'));
      return;
    }

    $result = $this->dailyReportService->sendDailyReport(new \DateTime('now'), $emails, TRUE);

    if (!empty($result['sent'])) {
      $this->messenger()->addStatus($this->t('Отчёт за сегодня успешно сформирован и отправлен на: @recipients (Заполнено тестов: @count).', [
        '@recipients' => implode(', ', $result['recipients']),
        '@count' => $result['count'],
      ]));
    }
    else {
      $this->messenger()->addWarning($result['message'] ?? $this->t('Не удалось отправить отчёт.'));
    }
  }

  /**
   * Helper to persist config values from form state.
   */
  protected function saveConfigValues(FormStateInterface $form_state) {
    $this->config('quiz_test.daily_report.settings')
      ->set('enabled', (bool) $form_state->getValue('enabled'))
      ->set('recipients', trim((string) $form_state->getValue('recipients')))
      ->set('subject', trim((string) $form_state->getValue('subject')))
      ->set('send_hour', (int) $form_state->getValue('send_hour'))
      ->set('weekdays_only', (bool) $form_state->getValue('weekdays_only'))
      ->set('include_details', (bool) $form_state->getValue('include_details'))
      ->set('send_empty', (bool) $form_state->getValue('send_empty'))
      ->save();
  }

}
