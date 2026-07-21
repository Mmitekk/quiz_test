# Quiz Test

Drupal-модуль для создания и проведения онлайн-тестирования с вопросами с несколькими вариантами ответов, email-уведомлениями о результатах и ролевым доступом.

Drupal module for creating and conducting online tests with multiple-choice questions, email notifications, and role-based access control.

---

<details>
<summary><b>English 🇬🇧</b></summary>

## Description

**Quiz Test** is a custom Drupal module that provides a complete testing system: administrators create tests with multiple-choice questions, configure who can take them, and receive detailed results by email.

The module uses Drupal content entities (`quiz_test`, `quiz_test_question`) — tests are fully compatible with Views, REST API, and Drupal's entity system. Questions support an unlimited number of answer options stored as JSON, with AJAX-powered dynamic form for adding/removing options.

## Features

| Feature | Description |
|---------|-------------|
| **Test entities** | Create, edit, publish/unpublish, and delete tests. Each test has title, description, email config, and author |
| **Question entities** | Unlimited questions per test with 2+ answer options. Questions are sorted by weight |
| **AJAX-powered form** | Dynamic add/remove of answer options in the question form without page reload |
| **Answer storage** | Answers stored as JSON array — no limit on the number of options per question |
| **Public test form** | Styled front-end form with radio buttons, instructions, and a full name field |
| **Result calculation** | Automatic scoring: correct count, total, percentage |
| **Email notifications** | HTML email with full question-by-question results, styled template |
| **Custom HTML mailer** | Dedicated mail plugin (`QuizTestPhpMail`) preserves HTML structure without word-wrapping |
| **Configurable recipients** | Email recipients per test (override global defaults) |
| **Subject line tokens** | `@test_title`, `@user_name`, `@score` placeholders in email subject |
| **Role-based access** | Configurable role that can take tests. Admins always have access |
| **Permissions** | Two permissions: `administer quiz tests` and `take quiz tests` |
| **Admin UI** | Test list with question count, status, author, custom operations (Questions, View) |
| **Tabs on test edit** | Edit / Questions tabs for easy navigation |
| **Results privacy** | Detailed results are emailed only — the user sees a generic thank-you page |
| **Tempstore** | Results stored in `tempstore.private` for post-submit redirect handling |
| **Logging** | Watchdog logs for test/question creation and deletion |
| **Cascading config** | Test-level email settings override global defaults from settings page |

## Requirements

- Drupal 9 or 10
- PHP 7.4+
- Core modules: `user`, `field`, `options`

## Installation

### Option 1: Composer (recommended)

Composer will download the module to the correct directory and handle updates.

1. Add the repository to your project's `composer.json`:
   ```bash
   composer config repositories.quiz_test vcs https://github.com/Mmitekk/quiz_test.git
   ```

2. Install the module:
   ```bash
   composer require mmitekk/quiz_test:^1.0
   ```
   The module will be placed in `web/modules/custom/quiz_test/` (path depends on your project structure).

3. Enable the module via Drush or admin interface:
   ```bash
   drush en quiz_test -y
   ```

4. The module will automatically create default configuration.

**Update via Composer:**
```bash
composer update mmitekk/quiz_test
drush updb -y
drush cr
```

> **Note:** If the module is installed outside `web/modules/custom/`, add `installer-paths` to your `composer.json`:
> ```json
> "extra": {
>     "installer-paths": {
>         "web/modules/custom/{$name}": ["type:drupal-custom-module"]
>     }
> }
> ```

### Option 2: Manual

1. Download the archive from [GitHub Releases](https://github.com/Mmitekk/quiz_test/releases)
2. Extract and rename the folder to `quiz_test`
3. Copy `quiz_test` into your Drupal installation's `modules/custom/` directory
4. Enable the module via admin interface (`/admin/modules`) or Drush:
   ```bash
   drush en quiz_test -y
   ```

## Setup & Usage

### Step 1. Configure module settings

Go to **Administration → Configuration → Quiz Test Settings** (`/admin/config/quiz-test/settings`):

| Setting | Description |
|---------|-------------|
| **Access role** | Select which role is allowed to take tests (administrators always have access) |
| **Default email recipients** | Comma-separated default email recipients for test results |
| **Default email subject** | Default subject line with `@test_title`, `@user_name`, `@score` tokens |

### Step 2. Create a test

1. Go to **Administration → Content → Tests** (`/admin/quiz-test`)
2. Click **Add test**
3. Fill in:
   - **Title** — test name displayed to users
   - **Description** — instructions or description shown before the test
   - **Email recipients** — (optional) override global recipients for this test
   - **Email subject** — (optional) override global subject for this test
   - **Published** — whether the test is available for taking
4. Save the test

### Step 3. Add questions

1. On the test edit page, switch to the **Questions** tab
2. Click **Add question**
3. Fill in:
   - **Question text** — the question text
   - **Answers** — add answer options (minimum 2). Use `+ Add option` to add more, click `Remove` to delete
   - **Correct answer** — select the correct option from the dropdown
   - **Weight** — sort order (lower = higher)
4. Save the question
5. Repeat for all questions

### Step 4. Take a test

1. Users with the configured role visit `/quiz-test/{test-id}`
2. They enter their full name and answer all questions
3. After submission:
   - Results are automatically scored
   - An HTML email with detailed results is sent to configured recipients
   - The user sees a thank-you page

## Permissions

| Permission | Description | Restrict access |
|------------|-------------|:---:|
| `administer quiz tests` | Create, edit, delete tests and questions | Yes |
| `take quiz tests` | Take tests on the site | — |

Configure at **Administration → People → Permissions** (`/admin/people/permissions`).

## Routes

| Path | Route | Description |
|------|-------|-------------|
| `/admin/quiz-test` | `entity.quiz_test.collection` | Test list (admin) |
| `/admin/quiz-test/add` | `entity.quiz_test.add_form` | Add new test |
| `/admin/quiz-test/{id}/edit` | `entity.quiz_test.edit_form` | Edit test |
| `/admin/quiz-test/{id}/delete` | `entity.quiz_test.delete_form` | Delete test |
| `/admin/quiz-test/{id}/questions` | `quiz_test.question_list` | Question list for a test |
| `/admin/quiz-test/{id}/questions/add` | `quiz_test.question_add` | Add question |
| `/admin/quiz-test/{id}/questions/{qid}/edit` | `quiz_test.question_edit` | Edit question |
| `/admin/quiz-test/{id}/questions/{qid}/delete` | `quiz_test.question_delete` | Delete question |
| `/admin/config/quiz-test/settings` | `quiz_test.settings` | Module settings |
| `/quiz-test/{id}` | `quiz_test.take_test` | Take test (public) |

## Module structure

```
quiz_test/
├── composer.json
├── quiz_test.info.yml                  — Module info
├── quiz_test.module                    — Hooks (help, theme, mail, entity hooks)
├── quiz_test.install                   — Schema, install/uninstall, updates
├── quiz_test.routing.yml               — Routes (questions CRUD, settings, take test)
├── quiz_test.permissions.yml           — Permissions
├── quiz_test.links.action.yml          — Action links (Add test, Add question)
├── quiz_test.links.menu.yml            — Menu items
├── quiz_test.links.task.yml            — Local tasks (tabs: Edit, Questions, Settings)
├── quiz_test.libraries.yml             — CSS libraries
├── config/
│   └── schema/quiz_test.schema.yml     — Config schema
├── css/
│   ├── quiz_test.admin.css             — Admin styles (questions table, answers form)
│   └── quiz_test.form.css              — Public test form styles
├── templates/
│   ├── quiz-test-results-email.html.twig   — HTML email template
│   └── quiz-test-results-email.txt.twig    — Plain-text email template
└── src/
    ├── Controller/
    │   └── QuizTestController.php       — Questions CRUD, take test controller
    ├── Entity/
    │   ├── QuizTest.php                 — Quiz test entity (fields: title, description, email, status)
    │   └── QuizTestQuestion.php         — Question entity (fields: test_id, question_text, answers JSON, correct_answer, weight)
    ├── Form/
    │   ├── QuizTestForm.php             — Add/edit test form
    │   ├── QuizTestDeleteForm.php       — Delete test form (cascade deletes questions)
    │   ├── QuizTestQuestionForm.php     — AJAX-powered question form (dynamic answers)
    │   ├── QuizTestQuestionDeleteForm.php — Delete question form
    │   ├── QuizTestListBuilder.php      — Custom admin list builder
    │   ├── QuizTestSettingsForm.php     — Module settings form
    │   └── TakeTestForm.php             — Public test-taking form
    └── Plugin/Mail/
        └── QuizTestPhpMail.php          — Custom HTML mail plugin (no word-wrapping)
```

## Email system

The module sends test results via a custom mail pipeline:

1. **`hook_mail()`** (`quiz_test.module`) — builds the email body using `quiz_test_results_email` theme
2. **`quiz-test-results-email.html.twig`** — full HTML template with table layout, red accent header, per-question results (correct/incorrect), and score summary
3. **`quiz-test-results-email.txt.twig`** — plain-text fallback template
4. **`hook_mail_alter()`** — sets `Content-Type: text/html; charset=UTF-8`
5. **`QuizTestPhpMail`** — custom mail plugin that skips word-wrapping for HTML emails (preserves HTML structure)

Email subject supports tokens:
- `@test_title` — test name
- `@user_name` — Drupal username of the test taker
- `@score` — percentage score (e.g., `80%`)

## Compatibility

| Drupal | PHP | Status |
|--------|-----|--------|
| 9.x | 7.4+ | Full support |
| 10.x | 8.1+ | Full support |

## Current version

**1.0.0** — see [Releases](https://github.com/Mmitekk/quiz_test/releases) for downloads and changelog.

## License

GPL-2.0-or-later, same as Drupal core.

## Author

**Mmitekk** — [https://github.com/Mmitekk](https://github.com/Mmitekk)

## Links

- **Repository:** [https://github.com/Mmitekk/quiz_test](https://github.com/Mmitekk/quiz_test)
- **Releases:** [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
- **Issues:** [https://github.com/Mmitekk/quiz_test/issues](https://github.com/Mmitekk/quiz_test/issues)

</details>

<details>
<summary><b>Русский 🇷🇺</b></summary>

## Описание

**Quiz Test** — пользовательский Drupal-модуль, предоставляющий полноценную систему тестирования: администраторы создают тесты с вопросами с несколькими вариантами ответов, настраивают, кто может их проходить, и получают детальные результаты по email.

Модуль использует Drupal content entities (`quiz_test`, `quiz_test_question`) — тесты полностью совместимы с Views, REST API и штатной entity-системой Drupal. Вопросы поддерживают неограниченное количество вариантов ответа (хранятся как JSON), с AJAX-формой для динамического добавления/удаления опций.

## Возможности

| Возможность | Описание |
|-------------|----------|
| **Сущности тестов** | Создание, редактирование, публикация/снятие с публикации, удаление. У каждого теста: название, описание, настройки email, автор |
| **Сущности вопросов** | Неограниченное количество вопросов на тест, от 2 вариантов ответа. Сортировка по весу |
| **AJAX-форма** | Динамическое добавление/удаление вариантов ответа без перезагрузки страницы |
| **Хранение ответов** | Варианты ответа хранятся как JSON-массив — нет ограничения на количество опций |
| **Форма прохождения** | Стилизованная форма с radio buttons, инструкциями и полем ФИО |
| **Подсчёт результатов** | Автоматический расчёт: правильно / всего, процент |
| **Email-уведомления** | HTML-письмо с детальными результатами по каждому вопросу, стилизованный шаблон |
| **Собственный HTML-майлер** | Плагин `QuizTestPhpMail` сохраняет структуру HTML без переноса строк |
| **Настраиваемые получатели** | Получатели на уровне теста (переопределяют глобальные) |
| **Плейсхолдеры темы** | `@test_title`, `@user_name`, `@score` в теме письма |
| **Ролевой доступ** | Выбор роли для прохождения тестов. Администраторы имеют доступ всегда |
| **Разрешения** | Два разрешения: `administer quiz tests` и `take quiz tests` |
| **Административный интерфейс** | Список тестов с количеством вопросов, статусом, автором, операциями (Вопросы, Просмотр) |
| **Вкладки при редактировании** | Вкладки «Редактировать» / «Вопросы» для удобной навигации |
| **Конфиденциальность результатов** | Детальные результаты только в email — пользователь видит общую страницу благодарности |
| **Tempstore** | Результаты сохраняются в `tempstore.private` для POST-redirect |
| **Логирование** | Watchdog-логи при создании и удалении тестов и вопросов |
| **Каскадная конфигурация** | Настройки email на уровне теста переопределяют глобальные значения |

## Требования

- Drupal 9 или 10
- PHP 7.4+
- Ядерные модули: `user`, `field`, `options`

## Установка

### Вариант 1: Через Composer (рекомендуется)

Composer автоматически скачает модуль в нужную директорию и будет управлять обновлениями.

1. Добавьте репозиторий модуля в `composer.json` вашего проекта:
   ```bash
   composer config repositories.quiz_test vcs https://github.com/Mmitekk/quiz_test.git
   ```

2. Установите модуль:
   ```bash
   composer require mmitekk/quiz_test:^1.0
   ```
   Модуль будет установлен в `web/modules/custom/quiz_test/` (путь зависит от структуры вашего проекта).

3. Включите модуль через Drush или админку:
   ```bash
   drush en quiz_test -y
   ```

4. Модуль автоматически создаст конфигурацию по умолчанию.

**Обновление через Composer:**
```bash
composer update mmitekk/quiz_test
drush updb -y
drush cr
```

> **Примечание:** если Composer устанавливает модуль не в `web/modules/custom/`, а в другую директорию — добавьте в `composer.json` проекта секцию `installer-paths`:
> ```json
> "extra": {
>     "installer-paths": {
>         "web/modules/custom/{$name}": ["type:drupal-custom-module"]
>     }
> }
> ```

### Вариант 2: Вручную

1. Скачайте архив с [GitHub Releases](https://github.com/Mmitekk/quiz_test/releases)
2. Распакуйте и переименуйте папку в `quiz_test`
3. Скопируйте папку `quiz_test` в директорию `modules/custom/` вашей Drupal-установки
4. Включите модуль через админку (`/admin/modules`) или Drush:
   ```bash
   drush en quiz_test -y
   ```

## Настройка и использование

### Шаг 1. Настройте параметры модуля

Перейдите в **Администрирование → Конфигурация → Настройки тестирования** (`/admin/config/quiz-test/settings`):

| Параметр | Описание |
|----------|----------|
| **Роль для доступа** | Выберите роль, которой разрешено проходить тесты (администраторы имеют доступ всегда) |
| **Получатели email по умолчанию** | Email-адреса через запятую для получения результатов |
| **Тема письма по умолчанию** | Шаблон темы с плейсхолдерами `@test_title`, `@user_name`, `@score` |

### Шаг 2. Создайте тест

1. Перейдите в **Администрирование → Содержимое → Тесты** (`/admin/quiz-test`)
2. Нажмите **Добавить тест**
3. Заполните:
   - **Название** — название теста, отображаемое пользователям
   - **Описание** — инструкции или описание перед тестом
   - **Получатели email** — (опционально) переопределить глобальных получателей для этого теста
   - **Тема email** — (опционально) переопределить глобальную тему для этого теста
   - **Опубликовано** — доступен ли тест для прохождения
4. Сохраните тест

### Шаг 3. Добавьте вопросы

1. На странице редактирования теста переключитесь на вкладку **Вопросы**
2. Нажмите **Добавить вопрос**
3. Заполните:
   - **Текст вопроса** — формулировка вопроса
   - **Варианты ответов** — добавьте варианты (минимум 2). Используйте `+ Добавить вариант`, чтобы добавить ещё, нажмите `Удалить`, чтобы убрать
   - **Верный ответ** — выберите правильный вариант из выпадающего списка
   - **Вес** — порядок сортировки (меньше = выше)
4. Сохраните вопрос
5. Повторите для всех вопросов

### Шаг 4. Пройдите тест

1. Пользователи с настроенной ролью переходят по адресу `/quiz-test/{id-теста}`
2. Они вводят своё ФИО и отвечают на все вопросы
3. После отправки:
   - Результаты автоматически подсчитываются
   - HTML-письмо с детальными результатами отправляется настроенным получателям
   - Пользователь видит страницу благодарности

## Разрешения

| Разрешение | Описание | Ограниченный доступ |
|------------|----------|:---:|
| `administer quiz tests` | Создание, редактирование и удаление тестов и вопросов | Да |
| `take quiz tests` | Прохождение тестов на сайте | — |

Настраивается в **Администрирование → Люди → Права доступа** (`/admin/people/permissions`).

## Маршруты

| Путь | Маршрут | Описание |
|------|---------|----------|
| `/admin/quiz-test` | `entity.quiz_test.collection` | Список тестов (админка) |
| `/admin/quiz-test/add` | `entity.quiz_test.add_form` | Добавить тест |
| `/admin/quiz-test/{id}/edit` | `entity.quiz_test.edit_form` | Редактировать тест |
| `/admin/quiz-test/{id}/delete` | `entity.quiz_test.delete_form` | Удалить тест |
| `/admin/quiz-test/{id}/questions` | `quiz_test.question_list` | Список вопросов теста |
| `/admin/quiz-test/{id}/questions/add` | `quiz_test.question_add` | Добавить вопрос |
| `/admin/quiz-test/{id}/questions/{qid}/edit` | `quiz_test.question_edit` | Редактировать вопрос |
| `/admin/quiz-test/{id}/questions/{qid}/delete` | `quiz_test.question_delete` | Удалить вопрос |
| `/admin/config/quiz-test/settings` | `quiz_test.settings` | Настройки модуля |
| `/quiz-test/{id}` | `quiz_test.take_test` | Пройти тест (публично) |

## Структура модуля

```
quiz_test/
├── composer.json
├── quiz_test.info.yml                  — Информация о модуле
├── quiz_test.module                    — Хуки (help, theme, mail, entity hooks)
├── quiz_test.install                   — Schema, install/uninstall, обновления
├── quiz_test.routing.yml               — Маршруты (CRUD вопросов, настройки, прохождение)
├── quiz_test.permissions.yml           — Права доступа
├── quiz_test.links.action.yml          — Ссылки действий (Добавить тест, Добавить вопрос)
├── quiz_test.links.menu.yml            — Пункты меню
├── quiz_test.links.task.yml            — Локальные вкладки (Редактировать, Вопросы, Настройки)
├── quiz_test.libraries.yml             — CSS-библиотеки
├── config/
│   └── schema/quiz_test.schema.yml     — Схема конфигурации
├── css/
│   ├── quiz_test.admin.css             — Стили админки (таблица вопросов, форма ответов)
│   └── quiz_test.form.css              — Стили публичной формы теста
├── templates/
│   ├── quiz-test-results-email.html.twig   — HTML-шаблон письма
│   └── quiz-test-results-email.txt.twig    — Текстовый шаблон письма
└── src/
    ├── Controller/
    │   └── QuizTestController.php       — Контроллер: вопросы CRUD, прохождение теста
    ├── Entity/
    │   ├── QuizTest.php                 — Сущность теста (поля: title, description, email, status)
    │   └── QuizTestQuestion.php         — Сущность вопроса (поля: test_id, question_text, answers JSON, correct_answer, weight)
    ├── Form/
    │   ├── QuizTestForm.php             — Форма добавления/редактирования теста
    │   ├── QuizTestDeleteForm.php       — Форма удаления теста (каскадное удаление вопросов)
    │   ├── QuizTestQuestionForm.php     — AJAX-форма вопроса (динамические ответы)
    │   ├── QuizTestQuestionDeleteForm.php — Форма удаления вопроса
    │   ├── QuizTestListBuilder.php      — Кастомный список тестов
    │   ├── QuizTestSettingsForm.php     — Форма настроек модуля
    │   └── TakeTestForm.php             — Публичная форма прохождения теста
    └── Plugin/Mail/
        └── QuizTestPhpMail.php          — Кастомный HTML-майлер (без переноса строк)
```

## Почтовый движок

Модуль отправляет результаты тестов через собственный почтовый конвейер:

1. **`hook_mail()`** (`quiz_test.module`) — формирует тело письма через тему `quiz_test_results_email`
2. **`quiz-test-results-email.html.twig`** — полный HTML-шаблон с табличной вёрсткой, красным акцентным заголовком, результатами по каждому вопросу (правильно/неправильно) и сводкой баллов
3. **`quiz-test-results-email.txt.twig`** — текстовый шаблон (запасной)
4. **`hook_mail_alter()`** — устанавливает `Content-Type: text/html; charset=UTF-8`
5. **`QuizTestPhpMail`** — кастомный плагин, отключающий перенос строк для HTML-писем (сохраняет структуру HTML)

Тема письма поддерживает плейсхолдеры:
- `@test_title` — название теста
- `@user_name` — Drupal-имя прошедшего тест
- `@score` — процент правильных ответов (например, `80%`)

## Совместимость

| Drupal | PHP | Статус |
|--------|-----|--------|
| 9.x | 7.4+ | Полная поддержка |
| 10.x | 8.1+ | Полная поддержка |

## Текущая версия

**1.0.0** — см. [Releases](https://github.com/Mmitekk/quiz_test/releases) для скачивания конкретной версии и истории изменений.

## Лицензия

GPL-2.0-or-later, как и ядро Drupal.

## Автор

**Mmitekk** — [https://github.com/Mmitekk](https://github.com/Mmitekk)

## Ссылки

- **Репозиторий:** [https://github.com/Mmitekk/quiz_test](https://github.com/Mmitekk/quiz_test)
- **Releases:** [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
- **Issues:** [https://github.com/Mmitekk/quiz_test/issues](https://github.com/Mmitekk/quiz_test/issues)
</details>
