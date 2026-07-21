<?php

namespace Drupal\quiz_test\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Quiz Test Open Question entity.
 *
 * Represents the optional second part of a test — open-ended questions with a
 * free-text answer (no predefined options, no automatic checking).
 *
 * @ingroup quiz_test
 *
 * @ContentEntityType(
 *   id = "quiz_test_open_question",
 *   label = @Translation("Открытый вопрос"),
 *   label_collection = @Translation("Открытые вопросы"),
 *   label_singular = @Translation("открытый вопрос"),
 *   label_plural = @Translation("открытых вопросов"),
 *   label_count = @PluralTranslation(
 *     singular = "@count открытый вопрос",
 *     plural = "@count открытых вопросов",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\quiz_test\Form\QuizTestOpenQuestionForm",
 *       "edit" = "Drupal\quiz_test\Form\QuizTestOpenQuestionForm",
 *       "delete" = "Drupal\quiz_test\Form\QuizTestOpenQuestionDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "quiz_test_open_question",
 *   admin_permission = "administer quiz tests",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "question_text",
 *   },
 *   links = {
 *     "canonical" = "/admin/quiz-test/{quiz_test}/open-questions/{quiz_test_open_question}",
 *   },
 * )
 */
class QuizTestOpenQuestion extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Gets the parent test entity.
   */
  public function getTest() {
    return $this->get('test_id')->entity;
  }

  /**
   * Gets the test ID.
   */
  public function getTestId() {
    return $this->get('test_id')->target_id;
  }

  /**
   * Sets the test ID.
   */
  public function setTestId($test_id) {
    $this->set('test_id', $test_id);
    return $this;
  }

  /**
   * Gets the question text.
   */
  public function getQuestionText() {
    return $this->get('question_text')->value;
  }

  /**
   * Sets the question text.
   */
  public function setQuestionText($text) {
    $this->set('question_text', $text);
    return $this;
  }

  /**
   * Returns whether the answer is required.
   */
  public function isRequired() {
    return (bool) $this->get('is_required')->value;
  }

  /**
   * Sets whether the answer is required.
   */
  public function setRequired($required) {
    $this->set('is_required', $required ? 1 : 0);
    return $this;
  }

  /**
   * Gets the weight.
   */
  public function getWeight() {
    return (int) $this->get('weight')->value;
  }

  /**
   * Sets the weight.
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * Gets the created time.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['test_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Тест'))
      ->setDescription(t('Тест, к которому относится данный открытый вопрос.'))
      ->setSetting('target_type', 'quiz_test')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['question_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Текст вопроса'))
      ->setDescription(t('Текст открытого вопроса.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 65535,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -4,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['is_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Обязательно для заполнения'))
      ->setDescription(t('Если включено — пользователь должен заполнить этот вопрос в форме прохождения теста.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Вес'))
      ->setDescription(t('Вес вопроса для сортировки (меньше = выше).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
        'settings' => [
          'min' => -100,
          'max' => 100,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Дата создания'))
      ->setDescription(t('Время создания вопроса.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Дата изменения'))
      ->setDescription(t('Время последнего изменения вопроса.'));

    return $fields;
  }

}
