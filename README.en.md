# Quiz Test

A Drupal 9 / 10 / 11 module that provides a complete online testing system: administrators create tests with multiple-choice questions, configure who can take them, and receive detailed results by email. Questions support an **unlimited** number of answer options, and the question form is AJAX-powered.

[Читать документацию на русском](./README.md)

## Features

- **Test entities** (`quiz_test`) — create, edit, publish/unpublish, delete. Each test has title, description, email config, and author. Fully compatible with Views, REST API and Drupal's native entity system
- **Question entities** (`quiz_test_question`) — unlimited questions per test, 2+ answer options each. Sorted by weight
- **AJAX-powered question form** — dynamic add/remove of answer options without page reload
- **JSON answer storage** — answer options are stored as a JSON array, no limit on the number of options
- **Styled public test form** — radio buttons, instructions, full-name field, dedicated CSS (`quiz_test.form.css`)
- **Automatic scoring** — correct / total, percentage
- **HTML email notifications** — email with detailed per-question results, styled template (`quiz-test-results-email.html.twig`)
- **Custom HTML mailer** (`QuizTestPhpMail`) — disables word-wrapping of core `PhpMail`, so the HTML structure of the email is preserved
- **Configurable recipients** — per-test recipients override the global defaults
- **Subject-line tokens** — `@test_title`, `@user_name`, `@score`
- **Role-based access** — pick the role allowed to take tests; administrators always have access
- **Permissions** — `administer quiz tests` and `take quiz tests`
- **Admin UI** — test list with question count, status, author and operations (Questions, View)
- **Tabs on test edit** — Edit / Questions / Settings
- **Result privacy** — detailed results are sent by email only; the user sees a generic thank-you page
- **Tempstore** — results are stored in `tempstore.private` for proper POST-redirect handling
- **Logging** — watchdog logs on test/question creation and deletion
- **Cascading config** — per-test email settings override the global defaults

## Requirements

- Drupal **9.x**, **10.x** or **11.x**
- PHP **7.4+** (8.1+ for Drupal 10, 8.3+ for Drupal 11)
- Core modules: `user`, `field`, `options`

## Installation

### Option 1: Via Composer (recommended)

Composer will automatically download the module into the correct directory (`modules/custom/quiz_test` relative to your site root) and manage updates. To pull from Mmitekk's GitHub repositories you must explicitly register the repository in your project.

1. **Add the module repository** to your project's `composer.json`:
   ```bash
   composer config repositories.quiz_test vcs https://github.com/Mmitekk/quiz_test.git
   ```
   The equivalent entry that will appear in `composer.json`:
   ```json
   "repositories": {
       "quiz_test": {
           "type": "vcs",
           "url": "https://github.com/Mmitekk/quiz_test.git"
       }
   }
   ```

2. **Install the module, pinning a concrete stable version** (not dev):
   ```bash
   composer require "mmitekk/quiz_test:^1.0"
   ```
   This installs release **1.0.0** (the `v1.0.0` tag). Composer installs the actual module version, not `dev-main` — because the repository ships git tags and the `^1.0` constraint resolves to a tagged release.
   The module will be placed in `web/modules/custom/quiz_test/` (in a standard `drupal/recommended-project` this is `modules/custom/` relative to the site root).

3. **Enable the module** via Drush or the admin UI:
   ```bash
   drush en quiz_test -y
   ```

4. On install the module automatically:
   - creates the `quiz_test.settings` configuration with default values;
   - registers its custom HTML mail plugin (`quiz_test_php_mail`) in `system.mail`.

**About the install path.** The standard `drupal/recommended-project` template already maps the `drupal-custom-module` type to `web/modules/custom/{$name}` — no extra configuration is needed. If Composer installs the module somewhere else, add (or adjust) the `installer-paths` section in your **project's** `composer.json`:
```json
"extra": {
    "installer-paths": {
        "web/modules/custom/{$name}": ["type:drupal-custom-module"]
    }
}
```
If your site root is the project root (no `web/` directory), use `"modules/custom/{$name}"` instead of `"web/modules/custom/{$name}"`.

**Updating via Composer:**
```bash
composer update mmitekk/quiz_test
drush updb -y
drush cr
```

### Option 2: Manually

1. Download the archive of the release you need: [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
2. Extract and rename the folder to `quiz_test`
3. Copy the `quiz_test` folder into the `modules/custom/` directory of your Drupal installation
4. Enable the module via the admin UI (`/admin/modules`) or Drush:
   ```bash
   drush en quiz_test -y
   ```

## Configuration & usage

### Step 1. Configure module settings

Go to **Administration → Configuration → Quiz Test Settings** (`/admin/config/quiz-test/settings`):

| Setting | Description |
|---------|-------------|
| **Access role** | Role allowed to take tests (administrators always have access) |
| **Default email recipients** | Comma-separated default recipients for test results |
| **Default email subject** | Default subject line with `@test_title`, `@user_name`, `@score` tokens |

### Step 2. Create a test

1. Go to **Administration → Content → Tests** (`/admin/quiz-test`)
2. Click **Add test**
3. Fill in:
   - **Title** — test name shown to users
   - **Description** — instructions or description shown before the test
   - **Email recipients** — (optional) override global recipients for this test
   - **Email subject** — (optional) override the global subject for this test
   - **Published** — whether the test is available for taking
4. Save the test

### Step 3. Add questions

1. On the test edit page, switch to the **Questions** tab
2. Click **Add question**
3. Fill in:
   - **Question text** — the question wording
   - **Answers** — add answer options (minimum 2). Use `+ Add option` to add more, click `Remove` to delete
   - **Correct answer** — select the correct option from the dropdown
   - **Weight** — sort order (lower = higher)
4. Save the question
5. Repeat for all questions

### Step 4. Take a test

1. Users with the configured role visit `/quiz-test/{test-id}`
2. They enter their full name and answer all questions
3. After submission:
   - results are scored automatically;
   - an HTML email with detailed results is sent to the configured recipients;
   - the user sees a thank-you page.

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
├── quiz_test.info.yml                  — Module info
├── quiz_test.module                    — Hooks (help, theme, mail, entity hooks)
├── quiz_test.install                   — Schema, install/uninstall, updates
├── quiz_test.routing.yml               — Routes (question CRUD, settings, take test)
├── quiz_test.permissions.yml           — Permissions
├── quiz_test.links.action.yml          — Action links (Add test, Add question)
├── quiz_test.links.menu.yml            — Menu items
├── quiz_test.links.task.yml            — Local tasks (tabs: Edit, Questions, Settings)
├── quiz_test.libraries.yml             — CSS libraries
├── composer.json                       — Composer metadata
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
    │   └── QuizTestController.php       — Question CRUD + take-test controller
    ├── Entity/
    │   ├── QuizTest.php                 — Test entity (title, description, email, status)
    │   └── QuizTestQuestion.php         — Question entity (test_id, question_text, answers JSON, correct_answer, weight)
    ├── Form/
    │   ├── QuizTestForm.php             — Add/edit test form
    │   ├── QuizTestDeleteForm.php       — Delete test form (cascades to questions)
    │   ├── QuizTestQuestionForm.php     — AJAX question form (dynamic answers)
    │   ├── QuizTestQuestionDeleteForm.php — Delete question form
    │   ├── QuizTestListBuilder.php      — Custom admin list builder
    │   ├── QuizTestSettingsForm.php     — Module settings form
    │   └── TakeTestForm.php             — Public test-taking form
    └── Plugin/Mail/
        └── QuizTestPhpMail.php          — Custom HTML mailer (no word-wrapping)
```

## Mail pipeline

The module sends test results through a custom mail pipeline:

1. **`hook_mail()`** (`quiz_test.module`) — builds the email body using the `quiz_test_results_email` theme
2. **`quiz-test-results-email.html.twig`** — full HTML template with a table layout, accent header, per-question results (correct/incorrect) and a score summary
3. **`quiz-test-results-email.txt.twig`** — plain-text fallback template
4. **`hook_mail_alter()`** — sets `Content-Type: text/html; charset=UTF-8` for the module's emails
5. **`QuizTestPhpMail`** — custom plugin that disables word-wrapping for HTML emails (preserves the HTML structure)

Email subject tokens:
- `@test_title` — test name
- `@user_name` — Drupal username of the test taker
- `@score` — percentage score (e.g. `80%`)

## Compatibility

| Drupal | PHP | Status |
|--------|-----|--------|
| 9.x | 7.4+ | Fully supported |
| 10.x | 8.1+ | Fully supported |
| 11.x | 8.3+ | Fully supported |

The module uses standard Drupal APIs (`@ContentEntityType`, `BaseFieldDefinition`, `EntityChangedTrait`) and correctly calls `accessCheck(TRUE)` on entity queries, meeting Drupal 10 / 11 requirements.

## Current version

**1.0.0** — see [Releases](https://github.com/Mmitekk/quiz_test/releases) to download a specific version.

## Uninstallation

1. Disable the module via the admin UI or Drush:
   ```bash
   drush pm:uninstall quiz_test -y
   ```
2. On uninstall, Drupal automatically removes the module's configuration and the `quiz_test` / `quiz_test_question` tables.

## License

GPL-2.0-or-later, same as Drupal core.

## Author

- **Mmitekk** — [https://github.com/Mmitekk](https://github.com/Mmitekk)

## Links

- **Repository:** [https://github.com/Mmitekk/quiz_test](https://github.com/Mmitekk/quiz_test)
- **Releases:** [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
- **Issues:** [https://github.com/Mmitekk/quiz_test/issues](https://github.com/Mmitekk/quiz_test/issues)
- **Документация на русском:** [README.md](./README.md)
