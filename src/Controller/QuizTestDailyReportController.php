<?php

namespace Drupal\quiz_test\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\quiz_test\Service\QuizTestDailyReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for viewing the daily test report on the website.
 */
class QuizTestDailyReportController extends ControllerBase {

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
   * Access check for viewing the daily report.
   *
   * Only allows users who are logged in and possess either the
   * 'view quiz test daily reports' or 'administer quiz tests' permission.
   * Also verifies that the web-view feature is enabled in module settings.
   */
  public function accessReport($date, Request $request) {
    $config = $this->config('quiz_test.daily_report.settings');
    $web_enabled = $config->get('enable_web_view') ?? TRUE;
    if (!$web_enabled) {
      return AccessResult::forbidden('Веб-версия отчёта отключена в настройках модуля.');
    }

    $account = $this->currentUser();
    if ($account->hasPermission('administer quiz tests') || $account->hasPermission('view quiz test daily reports')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::forbidden('У вас нет прав для просмотра ежедневных отчётов по тестированию.');
  }

  /**
   * Redirects legacy URLs (/quiz-test/daily-report/{date}) to /admin/quiz-test/daily-report/{date}.
   */
  public function legacyRedirect($date, Request $request) {
    $url = Url::fromRoute('quiz_test.daily_report_view', ['date' => $date]);
    return new TrustedRedirectResponse($url->toString(), 301);
  }

  /**
   * Redirects to the daily report view for today.
   */
  public function viewToday(Request $request) {
    $timezone = $this->config('system.date')->get('timezone.default') ?: date_default_timezone_get();
    $today_str = (new \DateTime('now', new \DateTimeZone($timezone)))->format('Y-m-d');
    $url = Url::fromRoute('quiz_test.daily_report_view', ['date' => $today_str]);
    return new TrustedRedirectResponse($url->toString());
  }

  /**
   * Title callback for the report view.
   */
  public function reportTitle($date) {
    return $this->t('Ежедневный отчёт по тестированию за @date', ['@date' => $date]);
  }

  /**
   * Displays the human-readable HTML daily report.
   */
  public function viewReport($date, Request $request) {
    // Validate date format YYYY-MM-DD.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      throw new NotFoundHttpException('Неверный формат даты отчёта.');
    }

    $timezone = $this->config('system.date')->get('timezone.default') ?: date_default_timezone_get();
    try {
      $dt = new \DateTime($date . ' 12:00:00', new \DateTimeZone($timezone));
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException('Некорректная дата отчёта.');
    }

    $report_data = $this->dailyReportService->buildReportData($dt);
    // Render with $is_web_view = TRUE so that redundant links are not rendered.
    $html = $this->dailyReportService->renderHtmlReport($report_data, TRUE, TRUE);

    // Return as standalone HTML document with strict noindex/privacy headers.
    $response = new Response($html);
    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
    $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
    $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    return $response;
  }

}
