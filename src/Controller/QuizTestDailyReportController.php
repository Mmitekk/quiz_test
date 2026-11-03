<?php

namespace Drupal\quiz_test\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
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
   * Custom access check for viewing the daily report.
   *
   * Grants access if the user has administrative permission OR provides
   * the valid secret token in the query string (?token=XYZ).
   */
  public function accessReport($date, Request $request) {
    $account = $this->currentUser();
    if ($account->hasPermission('administer quiz tests')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $token = (string) $request->query->get('token');
    $config_token = (string) $this->config('quiz_test.daily_report.settings')->get('access_token');

    if (!empty($config_token) && !empty($token) && hash_equals($config_token, $token)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::forbidden('У вас нет доступа к просмотру данного отчёта.');
  }

  /**
   * Redirects to the daily report view for today.
   */
  public function viewToday(Request $request) {
    $timezone = $this->config('system.date')->get('timezone.default') ?: date_default_timezone_get();
    $today_str = (new \DateTime('now', new \DateTimeZone($timezone)))->format('Y-m-d');
    $url = $this->dailyReportService->getWebReportUrl($today_str);
    return new TrustedRedirectResponse($url);
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
    $html = $this->dailyReportService->renderHtmlReport($report_data, TRUE);

    // Return as standalone HTML document so recipients can view it cleanly.
    $response = new Response($html);
    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
    return $response;
  }

}
