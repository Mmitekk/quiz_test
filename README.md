# Quiz Test

Drupal module for creating and conducting online tests with multiple-choice questions.

---

<details>
<summary><b>English 🇬🇧</b></summary>

## Description

**Quiz Test** is a custom Drupal module that allows administrators to create tests with multiple-choice questions, configure email notifications with results, and manage access permissions by role.

## Features

- Create, edit, and delete tests (content entities)
- Unlimited multiple-choice questions per test with custom answer count (2+)
- AJAX-powered dynamic question form (add/remove answer options)
- Sort questions by weight
- Role-based access control — select which role can take tests
- Front-end test-taking form with radio buttons
- Automatic scoring (correct / total, percentage)
- Email notifications with full question-by-question results
- HTML email templates with styled layout
- Configurable email recipients per test or globally
- Custom subject line with tokens (`@test_title`, `@user_name`, `@score`)
- Custom HTML mail plugin (preserves HTML structure, no word-wrapping)
- Admin test list with question count, status, and operations
- Permission-based access (`administer quiz tests`, `take quiz tests`)

## Requirements

- Drupal 9 or 10
- Core modules: `user`, `field`, `options`

## Installation

1. Place the `quiz_test` folder in your Drupal installation's `modules/custom/` directory.
2. Go to **Administration → Extend** and enable the module.
3. Configure settings at **Administration → Configuration → Quiz Test Settings**.
4. Create tests at **Administration → Content → Tests**.

## Usage

1. Create a test — add title, description, and email notification settings.
2. Add questions — each question supports 2 or more answer options, mark the correct one.
3. Configure which role can take tests in the settings page.
4. Users with the appropriate role visit `/quiz-test/{test-id}` to take the test.
5. After submission, results are emailed to the configured recipients.

## Permissions

| Permission | Description |
|---|---|
| `administer quiz tests` | Create, edit, delete tests and questions |
| `take quiz tests` | Take tests on the site |

## Routes

| Path | Description |
|---|---|
| `/admin/quiz-test` | Test list (admin) |
| `/admin/quiz-test/add` | Add new test |
| `/admin/quiz-test/{id}/edit` | Edit test |
| `/admin/quiz-test/{id}/delete` | Delete test |
| `/admin/quiz-test/{id}/questions` | Question list |
| `/admin/quiz-test/{id}/questions/add` | Add question |
| `/admin/quiz-test/{id}/questions/{qid}/edit` | Edit question |
| `/admin/quiz-test/{id}/questions/{qid}/delete` | Delete question |
| `/admin/config/quiz-test/settings` | Module settings |
| `/quiz-test/{id}` | Take test (public) |

</details>

<details>
<summary><b>Русский 🇷🇺</b></summary>

## Описание

**Quiz Test** — пользовательский Drupal-модуль для создания и проведения онлайн-тестирования с вопросами с несколькими вариантами ответов, email-уведомлениями о результатах и управлением доступом по ролям.

## Возможности

- Создание, редактирование и удаление тестов (content entities)
- Неограниченное количество вопросов с произвольным числом вариантов ответа (2+)
- AJAX-форма для динамического добавления/удаления вариантов ответа
- Сортировка вопросов по весу
- Ролевой доступ — выбор роли, которой разрешено проходить тесты
- Форма прохождения теста на фронтенде (radio buttons)
- Автоматический подсчёт результатов (правильно / всего, процент)
- Email-уведомления с детальными результатами по каждому вопросу
- HTML-шаблоны писем со стилизацией
- Настраиваемые получатели email (на уровне теста или глобально)
- Шаблон темы письма с плейсхолдерами (`@test_title`, `@user_name`, `@score`)
- Собственный HTML-почтовый плагин (сохраняет структуру HTML без переноса строк)
- Список тестов в админке с количеством вопросов, статусом и операциями
- Два разрешения (`administer quiz tests`, `take quiz tests`)

## Требования

- Drupal 9 или 10
- Ядраные модули: `user`, `field`, `options`

## Установка

1. Поместите папку `quiz_test` в директорию `modules/custom/` вашей Drupal-установки.
2. Перейдите в **Администрирование → Расширения** и включите модуль.
3. Настройте параметры в **Администрирование → Конфигурация → Настройки тестирования**.
4. Создавайте тесты в **Администрирование → Содержимое → Тесты**.

## Использование

1. Создайте тест — укажите название, описание и настройки email-уведомлений.
2. Добавьте вопросы — каждый вопрос поддерживает 2 и более варианта ответа, отметьте правильный.
3. Настройте, какой роли разрешено проходить тесты, на странице настроек.
4. Пользователи с соответствующей ролью переходят на `/quiz-test/{id-теста}` для прохождения.
5. После отправки результаты отправляются на настроенные email-адреса.

## Разрешения

| Разрешение | Описание |
|---|---|
| `administer quiz tests` | Создание, редактирование и удаление тестов и вопросов |
| `take quiz tests` | Прохождение тестов на сайте |

## Маршруты

| Путь | Описание |
|---|---|
| `/admin/quiz-test` | Список тестов (админка) |
| `/admin/quiz-test/add` | Добавить тест |
| `/admin/quiz-test/{id}/edit` | Редактировать тест |
| `/admin/quiz-test/{id}/delete` | Удалить тест |
| `/admin/quiz-test/{id}/questions` | Список вопросов |
| `/admin/quiz-test/{id}/questions/add` | Добавить вопрос |
| `/admin/quiz-test/{id}/questions/{qid}/edit` | Редактировать вопрос |
| `/admin/quiz-test/{id}/questions/{qid}/delete` | Удалить вопрос |
| `/admin/config/quiz-test/settings` | Настройки модуля |
| `/quiz-test/{id}` | Пройти тест (публично) |

</details>
