<?php

namespace Drupal\quiz_test\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Quiz Test Question entity.
 *
 * @ingroup quiz_test
 *
 * @ContentEntityType(
 *   id = "quiz_test_question",
 *   label = @Translation("Вопрос"),
 *   label_collection = @Translation("Вопросы"),
 *   label_singular = @Translation("вопрос"),
 *   label_plural = @Translation("вопросов"),
 *   label_count = @PluralTranslation(
 *     singular = "@count вопрос",
 *     plural = "@count вопросов",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\quiz_test\Form\QuizTestQuestionForm",
 *       "edit" = "Drupal\quiz_test\Form\QuizTestQuestionForm",
 *       "delete" = "Drupal\quiz_test\Form\QuizTestQuestionDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "quiz_test_question",
 *   admin_permission = "administer quiz tests",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "question_text",
 *   },
 *   links = {
 *     "canonical" = "/admin/quiz-test/{quiz_test}/questions/{quiz_test_question}",
 *   },
 * )
 */
class QuizTestQuestion extends ContentEntityBase {

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
   * Sets the test entity.
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
   * Gets all answers as an indexed array.
   *
   * @return array
   *   Array of answer option strings.
   */
  public function getAnswers() {
    $value = $this->get('answers')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? array_values($decoded) : [];
  }

  /**
   * Sets answers from an array.
   *
   * @param array $answers
   *   Array of answer option strings.
   *
   * @return $this
   */
  public function setAnswers(array $answers) {
    $filtered = [];
    foreach ($answers as $answer) {
      if ($answer !== NULL && $answer !== '') {
        $filtered[] = $answer;
      }
    }
    $this->set('answers', json_encode(array_values($filtered), JSON_UNESCAPED_UNICODE));
    return $this;
  }

  /**
   * Gets the correct answer index (0-based).
   */
  public function getCorrectAnswer() {
    return (int) $this->get('correct_answer')->value;
  }

  /**
   * Sets the correct answer index.
   *
   * @param int $index
   *   The 0-based index of the correct answer.
   *
   * @return $this
   */
  public function setCorrectAnswer($index) {
    $this->set('correct_answer', (int) $index);
    return $this;
  }

  /**
   * Gets the correct answer text.
   */
  public function getCorrectAnswerText() {
    $answers = $this->getAnswers();
    $idx = $this->getCorrectAnswer();
    return $answers[$idx] ?? '';
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
      ->setDescription(t('Тест, к которому относится данный вопрос.'))
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
      ->setDescription(t('Текст вопроса.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 65535,
      ])
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => -4,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['answers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Варианты ответов'))
      ->setDescription(t('Варианты ответов на вопрос (хранятся как JSON-массив).'))
      ->setRequired(FALSE)
      ->setSetting('case_sensitive', FALSE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['correct_answer'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Верный ответ'))
      ->setDescription(t('Индекс верного варианта ответа (начиная с 0).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 255)
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', FALSE)
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
