<?php

namespace Drupal\quiz_test\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Quiz Test entity.
 *
 * @ingroup quiz_test
 *
 * @ContentEntityType(
 *   id = "quiz_test",
 *   label = @Translation("Тест"),
 *   label_collection = @Translation("Тесты"),
 *   label_singular = @Translation("тест"),
 *   label_plural = @Translation("тесты"),
 *   label_count = @PluralTranslation(
 *     singular = "@count тест",
 *     plural = "@count тестов",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\quiz_test\Form\QuizTestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\quiz_test\Form\QuizTestForm",
 *       "edit" = "Drupal\quiz_test\Form\QuizTestForm",
 *       "delete" = "Drupal\quiz_test\Form\QuizTestDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "quiz_test",
 *   admin_permission = "administer quiz tests",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/quiz-test/add",
 *     "edit-form" = "/admin/quiz-test/{quiz_test}/edit",
 *     "delete-form" = "/admin/quiz-test/{quiz_test}/delete",
 *     "collection" = "/admin/quiz-test",
 *   },
 * )
 */
class QuizTest extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Gets the test title.
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * Sets the test title.
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * Gets the test description.
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * Sets the test description.
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * Gets the email recipients.
   */
  public function getEmailRecipients() {
    return $this->get('email_recipients')->value;
  }

  /**
   * Sets the email recipients.
   */
  public function setEmailRecipients($recipients) {
    $this->set('email_recipients', $recipients);
    return $this;
  }

  /**
   * Gets the email subject.
   */
  public function getEmailSubject() {
    return $this->get('email_subject')->value;
  }

  /**
   * Sets the email subject.
   */
  public function setEmailSubject($subject) {
    $this->set('email_subject', $subject);
    return $this;
  }

  /**
   * Gets the status.
   */
  public function getStatus() {
    return (bool) $this->get('status')->value;
  }

  /**
   * Sets the status.
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Returns whether the test is published.
   */
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * Gets the questions for this test.
   */
  public function getQuestions() {
    $query = \Drupal::entityTypeManager()->getStorage('quiz_test_question')->getQuery()
      ->condition('test_id', $this->id())
      ->sort('weight', 'ASC')
      ->sort('id', 'ASC')
      ->accessCheck(TRUE);
    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }
    // array_values re-indexes so foreach delta is sequential (0,1,2...)
    // even if some questions were deleted and IDs have gaps.
    $entities = \Drupal::entityTypeManager()->getStorage('quiz_test_question')->loadMultiple($ids);
    return array_values($entities);
  }

  /**
   * Gets the open questions for this test.
   *
   * @return \Drupal\quiz_test\Entity\QuizTestOpenQuestion[]
   *   Array of open question entities, ordered by weight then id.
   */
  public function getOpenQuestions() {
    $query = \Drupal::entityTypeManager()->getStorage('quiz_test_open_question')->getQuery()
      ->condition('test_id', $this->id())
      ->sort('weight', 'ASC')
      ->sort('id', 'ASC')
      ->accessCheck(TRUE);
    try {
      $ids = $query->execute();
    }
    catch (\Exception $e) {
      // The table may not exist yet (e.g. pending database updates). Treat
      // this as "no open questions" instead of crashing the whole page.
      \Drupal::logger('quiz_test')->warning('Could not query open questions for test @id: @message', [
        '@id' => $this->id(),
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    if (empty($ids)) {
      return [];
    }
    $entities = \Drupal::entityTypeManager()->getStorage('quiz_test_open_question')->loadMultiple($ids);
    return array_values($entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Название'))
      ->setDescription(t('Название теста.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Описание'))
      ->setDescription(t('Описание теста, отображаемое пользователям.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -4,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['email_recipients'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Получатели email'))
      ->setDescription(t('Введите адреса электронной почты через запятую. Результаты будут отправлены на эти адреса после прохождения теста.'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 2,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['email_subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Тема email'))
      ->setDescription(t('Тема письма с результатами тестирования. Оставьте пустым для значения по умолчанию.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Автор'))
      ->setDescription(t('Пользователь, создавший тест.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Опубликовано'))
      ->setDescription(t('Опубликован ли тест и доступен для прохождения.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Дата создания'))
      ->setDescription(t('Время создания теста.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Дата изменения'))
      ->setDescription(t('Время последнего изменения теста.'));

    return $fields;
  }

}
