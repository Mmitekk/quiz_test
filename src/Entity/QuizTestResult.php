<?php

namespace Drupal\quiz_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Quiz Test Result entity (a single test submission log entry).
 *
 * Each time a visitor submits a test, a snapshot of their answers is stored
 * here so administrators can see who passed which test and when.
 *
 * @ingroup quiz_test
 *
 * @ContentEntityType(
 *   id = "quiz_test_result",
 *   label = @Translation("Результат теста"),
 *   label_collection = @Translation("Результаты тестов"),
 *   label_singular = @Translation("результат"),
 *   label_plural = @Translation("результатов"),
 *   label_count = @PluralTranslation(
 *     singular = "@count результат",
 *     plural = "@count результатов",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "delete" = "Drupal\quiz_test\Form\QuizTestResultDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "quiz_test_result",
 *   admin_permission = "administer quiz tests",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "full_name",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "delete-form" = "/admin/quiz-test/results/{quiz_test_result}/delete",
 *   },
 * )
 */
class QuizTestResult extends ContentEntityBase {

  /**
   * Gets the test title snapshot taken at submission time.
   */
  public function getTestTitle() {
    return $this->get('test_title')->value;
  }

  /**
   * Gets the test ID.
   */
  public function getTestId() {
    return (int) $this->get('test_id')->target_id;
  }

  /**
   * Loads the parent test entity (NULL if the test no longer exists).
   */
  public function getTest() {
    return $this->get('test_id')->entity;
  }

  /**
   * Gets the respondent user ID (0 for anonymous).
   */
  public function getUid() {
    return (int) $this->get('uid')->target_id;
  }

  /**
   * Loads the respondent user account.
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * Gets the full name entered in the form.
   */
  public function getFullName() {
    return $this->get('full_name')->value;
  }

  /**
   * Gets the number of correct answers.
   */
  public function getCorrectCount() {
    return (int) $this->get('correct')->value;
  }

  /**
   * Gets the total number of multiple-choice questions.
   */
  public function getTotalCount() {
    return (int) $this->get('total')->value;
  }

  /**
   * Gets the score percentage (0 when the test had no scored questions).
   */
  public function getScorePercent() {
    return (int) $this->get('score_percent')->value;
  }

  /**
   * Whether the submission has a numeric score (i.e. had choice questions).
   */
  public function hasScore() {
    return $this->getTotalCount() > 0;
  }

  /**
   * Gets the created time.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Gets the decoded answers snapshot.
   *
   * @return array
   *   List of items:
   *   - choice: question, user_answer, user_answer_text, correct_answer,
   *     correct_answer_text, is_correct.
   *   - open: question, user_answer_text.
   */
  public function getAnswerData() {
    $value = $this->get('answers')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['test_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Тест'))
      ->setDescription(t('Тест, который проходил пользователь.'))
      ->setSetting('target_type', 'quiz_test')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Пользователь'))
      ->setDescription(t('Учётная запись сайта, под которой проходили тест.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', FALSE);

    $fields['test_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Название теста'))
      ->setDescription(t('Снимок названия теста на момент прохождения.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', FALSE);

    $fields['full_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ФИО'))
      ->setDescription(t('ФИО, указанное пользователем при прохождении теста.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', FALSE);

    $fields['correct'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Правильных ответов'))
      ->setDescription(t('Количество правильных ответов.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', FALSE);

    $fields['total'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Всего вопросов'))
      ->setDescription(t('Общее количество вопросов с вариантами ответа.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', FALSE);

    $fields['score_percent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Балл'))
      ->setDescription(t('Процент правильных ответов.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', FALSE);

    $fields['answers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Ответы'))
      ->setDescription(t('JSON-снимок всех ответов на момент прохождения.'))
      ->setRequired(FALSE)
      ->setSetting('case_sensitive', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Дата прохождения'))
      ->setDescription(t('Время отправки ответов.'));

    return $fields;
  }

}
