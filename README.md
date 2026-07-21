# Quiz Test

Модуль для Drupal 9 / 10 / 11, предоставляющий полноценную систему онлайн-тестирования: администраторы создают тесты с вопросами с несколькими вариантами ответов, настраивают, кто может их проходить, и получают детальные результаты по email. Вопросы поддерживают **неограниченное** количество вариантов ответа, а форма прохождения работает на AJAX.

[Read this documentation in English](./README.en.md)

## Возможности

- **Сущности тестов** (`quiz_test`) — создание, редактирование, публикация/снятие с публикации, удаление. У каждого теста: название, описание, настройки email, автор. Полностью совместимы с Views, REST API и штатной entity-системой Drupal
- **Сущности вопросов** (`quiz_test_question`) — неограниченное количество вопросов на тест, от 2 вариантов ответа. Сортировка по весу
- **AJAX-форма вопроса** — динамическое добавление/удаление вариантов ответа без перезагрузки страницы
- **Хранение ответов как JSON** — варианты ответа лежат в JSON-массиве, нет ограничения на количество опций
- **Стилизованная форма прохождения** — radio-кнопки, инструкции, поле ФИО, отдельный CSS (`quiz_test.form.css`)
- **Автоматический подсчёт результатов** — правильно / всего, процент
- **HTML email-уведомления** — письмо с детальными результатами по каждому вопросу, стилизованный шаблон (`quiz-test-results-email.html.twig`)
- **Собственный HTML-майлер** (`QuizTestPhpMail`) — отключает перенос строк (wordwrap) у core `PhpMail`, благодаря чему HTML-структура письма не ломается
- **Настраиваемые получатели** — получатели на уровне теста переопределяют глобальные
- **Плейсхолдеры темы письма** — `@test_title`, `@user_name`, `@score`
- **Ролевой доступ** — выбор роли для прохождения тестов; администраторы имеют доступ всегда
- **Разрешения** — `administer quiz tests` и `take quiz tests`
- **Административный интерфейс** — список тестов с количеством вопросов, статусом, автором и операциями (Вопросы, Просмотр)
- **Вкладки при редактировании** — «Редактировать» / «Вопросы» / «Настройки»
- **Конфиденциальность результатов** — детальные результаты уходят только в email, пользователь видит общую страницу благодарности
- **Tempstore** — результаты сохраняются в `tempstore.private` для корректной обработки POST-redirect
- **Логирование** — watchdog-логи при создании/удалении тестов и вопросов
- **Каскадная конфигурация** — настройки email на уровне теста переопределяют глобальные значения

## Требования

- Drupal **9.x**, **10.x** или **11.x**
- PHP **7.4+** (8.1+ для Drupal 10, 8.3+ для Drupal 11)
- Ядерные модули: `user`, `field`, `options`

## Установка

### Вариант 1: Через Composer (рекомендуется)

Composer автоматически скачает модуль в нужную директорию (`modules/custom/quiz_test` относительно корня сайта) и будет управлять обновлениями. Для скачивания с GitHub-репозиториев Mmitekk нужно явно подключить репозиторий к вашему проекту.

1. **Добавьте репозиторий модуля** в `composer.json` вашего проекта:
   ```bash
   composer config repositories.quiz_test vcs https://github.com/Mmitekk/quiz_test.git
   ```
   Эквивалентная запись, которая появится в `composer.json`:
   ```json
   "repositories": {
       "quiz_test": {
           "type": "vcs",
           "url": "https://github.com/Mmitekk/quiz_test.git"
       }
   }
   ```

2. **Установите модуль, указав конкретную стабильную версию** (не dev):
   ```bash
   composer require "mmitekk/quiz_test:^1.0"
   ```
   Эта команда поставит релиз **1.0.0** (тег `v1.0.0`). Composer установит именно версию модуля, а не `dev-main` — благодаря наличию git-тегов в репозитории и использованию ограничения `^1.0`.
   Модуль будет установлен в `web/modules/custom/quiz_test/` (в стандартном `drupal/recommended-project` это и есть `modules/custom/` относительно корня сайта).

3. **Включите модуль** через Drush или админку:
   ```bash
   drush en quiz_test -y
   ```

4. При установке модуль автоматически:
   - создаст конфигурацию `quiz_test.settings` со значениями по умолчанию;
   - подключит собственный HTML mail-плагин (`quiz_test_php_mail`) в `system.mail`.

**Важно про путь установки.** В стандартном шаблоне `drupal/recommended-project` тип `drupal-custom-module` уже автоматически монтируется в `web/modules/custom/{$name}` — ничего дополнительно настраивать не нужно. Если Composer ставит модуль не туда, добавьте (или приведите к такому виду) секцию `installer-paths` в `composer.json` **проекта**:
```json
"extra": {
    "installer-paths": {
        "web/modules/custom/{$name}": ["type:drupal-custom-module"]
    }
}
```
Если корень сайта у вас не в `web/`, а в корне проекта — используйте `"modules/custom/{$name}"` вместо `"web/modules/custom/{$name}"`.

**Обновление через Composer:**
```bash
composer update mmitekk/quiz_test
drush updb -y
drush cr
```

### Вариант 2: Вручную

1. Скачайте архив нужного релиза: [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
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
| **Роль для доступа** | Роль, которой разрешено проходить тесты (администраторы имеют доступ всегда) |
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
   - **Варианты ответов** — добавьте варианты (минимум 2). Используйте `+ Добавить вариант`, чтобы добавить ещё, `Удалить` — чтобы убрать
   - **Верный ответ** — выберите правильный вариант из выпадающего списка
   - **Вес** — порядок сортировки (меньше = выше)
4. Сохраните вопрос
5. Повторите для всех вопросов

### Шаг 4. Пройдите тест

1. Пользователи с настроенной ролью переходят по адресу `/quiz-test/{id-теста}`
2. Они вводят своё ФИО и отвечают на все вопросы
3. После отправки:
   - результаты автоматически подсчитываются;
   - HTML-письмо с детальными результатами отправляется настроенным получателям;
   - пользователь видит страницу благодарности.

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
├── quiz_test.info.yml                  — Информация о модуле
├── quiz_test.module                    — Хуки (help, theme, mail, entity hooks)
├── quiz_test.install                   — Schema, install/uninstall, обновления
├── quiz_test.routing.yml               — Маршруты (CRUD вопросов, настройки, прохождение)
├── quiz_test.permissions.yml           — Права доступа
├── quiz_test.links.action.yml          — Ссылки действий (Добавить тест, Добавить вопрос)
├── quiz_test.links.menu.yml            — Пункты меню
├── quiz_test.links.task.yml            — Локальные вкладки (Редактировать, Вопросы, Настройки)
├── quiz_test.libraries.yml             — CSS-библиотеки
├── composer.json                       — Composer-метаданные
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
    │   ├── QuizTest.php                 — Сущность теста (title, description, email, status)
    │   └── QuizTestQuestion.php         — Сущность вопроса (test_id, question_text, answers JSON, correct_answer, weight)
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
2. **`quiz-test-results-email.html.twig`** — полный HTML-шаблон с табличной вёрсткой, акцентным заголовком, результатами по каждому вопросу (правильно/неправильно) и сводкой баллов
3. **`quiz-test-results-email.txt.twig`** — текстовый шаблон (запасной)
4. **`hook_mail_alter()`** — устанавливает `Content-Type: text/html; charset=UTF-8` для писем модуля
5. **`QuizTestPhpMail`** — кастомный плагин, отключающий перенос строк для HTML-писем (сохраняет структуру HTML)

Тема письма поддерживает плейсхолдеры:
- `@test_title` — название теста
- `@user_name` — Drupal-имя прошедшего тест
- `@score` — процент правильных ответов (например, `80%`)

## Совместимость

| Drupal | PHP | Статус |
|--------|-----|--------|
| 9.x  | 7.4+ | Полная поддержка |
| 10.x | 8.1+ | Полная поддержка |
| 11.x | 8.3+ | Полная поддержка |

Модуль использует стандартные API Drupal (`@ContentEntityType`, `BaseFieldDefinition`, `EntityChangedTrait`) и корректно вызывает `accessCheck(TRUE)` на entity-запросах, что соответствует требованиям Drupal 10 / 11.

## Текущая версия

**1.0.0** — см. [Releases](https://github.com/Mmitekk/quiz_test/releases) для скачивания конкретной версии.

## Удаление модуля

1. Отключите модуль через админку или Drush:
   ```bash
   drush pm:uninstall quiz_test -y
   ```
2. При удалении Drupal автоматически удалит конфигурацию модуля и таблицы `quiz_test` / `quiz_test_question`.

## Лицензия

GPL-2.0-or-later, как и ядро Drupal.

## Автор

- **Mmitekk** — [https://github.com/Mmitekk](https://github.com/Mmitekk)

## Ссылки

- **Репозиторий:** [https://github.com/Mmitekk/quiz_test](https://github.com/Mmitekk/quiz_test)
- **Releases:** [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
- **Issues:** [https://github.com/Mmitekk/quiz_test/issues](https://github.com/Mmitekk/quiz_test/issues)
- **English documentation:** [README.en.md](./README.en.md)
