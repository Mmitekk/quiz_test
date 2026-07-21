<?php

namespace Drupal\quiz_test\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Custom mail plugin that supports HTML email without word-wrapping.
 *
 * Drupal core's PhpMail::format() always applies MailFormatHelper::wrapMail()
 * which breaks HTML tags by inserting line breaks every 77 characters.
 * This plugin overrides format() to skip word-wrapping when the html flag
 * is set, following the same approach as the Webform module.
 *
 * @Mail(
 *   id = "quiz_test_php_mail",
 *   label = @Translation("Quiz Test HTML Mailer"),
 *   description = @Translation("Sends HTML or plain text email using PHP's native mail() function.")
 * )
 */
class QuizTestPhpMail extends PhpMail {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join body parts into a single string.
    $message['body'] = implode("\n\n", $message['body']);

    // If html flag is set, skip word-wrapping to preserve HTML structure.
    if (!empty($message['params']['html'])) {
      return $message;
    }

    // Plain text: apply standard Drupal word wrapping.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);
    return $message;
  }

}
