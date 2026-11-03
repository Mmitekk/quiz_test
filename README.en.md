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
- **Submission log (since 1.2.0)** — an admin page showing who took which test and when, with the score. Each submission has a detailed report (answers to every question, including open ones), a test/full-name filter, and a pager. Question texts and options are snapshotted at submission time, so history stays accurate even after questions are edited
- **Daily summary report (since 1.3.0)** — automated end-of-day summary email (customizable hour, e.g. 20:00 or 18:00) dispatched via Cron to email addresses configured in the dedicated admin panel. The report includes key KPIs, a participant table (website username and submitted Full Name / ФИО), scores, a direct token-secured web link, and a complete breakdown of all answered multiple-choice and open-ended questions
- **Tempstore** — results are stored in `tempstore.private` for proper POST-redirect handling
- **Logging** — watchdog logs on test/question creation and deletion
- **Cascading config** — per-test email settings override the global defaults
- **Optional open part (since 1.1.0)** — a second part made of open-ended questions with a free-text answer (single-line field). It only appears in the take form and in the email if the test has at least one open question. Open answers do not affect the percentage score. Each open question has a "Required" checkbox

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
   composer require "mmitekk/quiz_test:^1.3"
   ```
   The module will be placed in `web/modules/custom/quiz_test/` (in a standard `drupal/recommended-project` this is `modules/custom/` relative to the site root).

3. **Enable the module** via Drush or the admin UI:
   ```bash
   drush en quiz_test -y
   ```

4. On install the module automatically:
   - creates the `quiz_test.settings` and `quiz_test.daily_report.settings` configurations with default values;
   - registers its custom HTML mail plugin (`quiz_test_php_mail`) in `system.mail`.

**Updating via Composer:**
```bash
composer update mmitekk/quiz_test
drush updb -y
drush cr
```

### Option 2: Manually

1. Download the archive for the required release: [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
2. Unpack and rename the folder to `quiz_test`
3. Copy `quiz_test` into the `modules/custom/` folder of your Drupal installation
4. Enable the module via the admin UI (`/admin/modules`) or Drush:
   ```bash
   drush en quiz_test -y
   ```

## Configuration & Usage

### Step 1. Configure module settings

Navigate to **Administration → Configuration → Quiz Test settings** (`/admin/config/quiz-test/settings`):

| Setting | Description |
|---------|-------------|
| **Role for access** | Role permitted to take tests (administrators always have access) |
| **Default email recipients** | Comma-separated email addresses to receive test results |
| **Default email subject** | Subject template with placeholders `@test_title`, `@user_name`, `@score` |

### Step 2. Create a test

1. Go to **Administration → Content → Tests** (`/admin/quiz-test`)
2. Click **Add test**
3. Fill in Title, Description, optional recipient overrides, and publication status.
4. Save the test.

### Step 3. Add questions

1. In the test edit screen, switch to the **Questions** tab.
2. Click **Add question**.
3. Fill in Question text, add answer options (min 2), and designate the correct answer.
4. Save the question.

### Step 3a. Add open questions (optional)

1. Switch to the **Open questions** tab on the test edit page.
2. Click **Add open question**, define the prompt, and choose whether it is required.
3. Save the open question.

### Step 4. Take the test

1. Authorized users visit `/quiz-test/{test-id}`.
2. They enter their Full Name (ФИО) and answer all questions.
3. Submissions trigger scoring, instant email notifications, and are recorded in the submission log.

### Step 5. Review the submission log

1. Visit **Administration → Content → Quiz results** (`/admin/quiz-test/results`).
2. Filter by test or participant name, and click **Results** to view detailed question-by-question responses.

### Step 6. Configure daily summary reports (since 1.3.0)

Navigate to **Administration → Configuration → Quiz Test Daily Report** (`/admin/config/quiz-test/daily-report`):

1. **Enable Daily Report**: Toggle automatic end-of-day dispatch via Cron.
2. **Email Recipients**: Enter recipient email addresses (comma or newline separated).
3. **Dispatch Schedule (Send Hour)**: Select the end-of-day trigger hour (`00:00` to `23:00`, default `20:00`). Dispatched on the first cron run after the chosen hour.
4. **Weekdays Only**: Optionally skip sending reports on Saturdays and Sundays.
5. **Content Options**: Toggle full question breakdown vs. summary-only, and toggle empty day skipping.
6. **Send Now Button**: Instantly tests email dispatch for today's submissions without waiting for cron.
7. **Web View Link**: Both the admin settings page and all sent emails include a secure tokenized link to view the report in a browser without requiring admin authentication.

## Permissions

| Permission | Description | Restricted |
|------------|-------------|:---:|
| `administer quiz tests` | Create, edit, delete tests, questions, access results log and report settings | Yes |
| `take quiz tests` | Take tests on the site | — |

Configured under **Administration → People → Permissions** (`/admin/people/permissions`).

## Routes

| Path | Route | Description |
|------|-------|-------------|
| `/admin/quiz-test` | `entity.quiz_test.collection` | Admin test list |
| `/admin/quiz-test/add` | `entity.quiz_test.add_form` | Add test |
| `/admin/quiz-test/{id}/edit` | `entity.quiz_test.edit_form` | Edit test |
| `/admin/quiz-test/{id}/delete` | `entity.quiz_test.delete_form` | Delete test |
| `/admin/quiz-test/{id}/questions` | `quiz_test.question_list` | Questions list |
| `/admin/quiz-test/{id}/questions/add` | `quiz_test.question_add` | Add question |
| `/admin/quiz-test/{id}/questions/{qid}/edit` | `quiz_test.question_edit` | Edit question |
| `/admin/quiz-test/{id}/questions/{qid}/delete` | `quiz_test.question_delete` | Delete question |
| `/admin/quiz-test/{id}/open-questions` | `quiz_test.open_question_list` | Open questions list |
| `/admin/quiz-test/{id}/open-questions/add` | `quiz_test.open_question_add` | Add open question |
| `/admin/quiz-test/{id}/open-questions/{qid}/edit` | `quiz_test.open_question_edit` | Edit open question |
| `/admin/quiz-test/{id}/open-questions/{qid}/delete` | `quiz_test.open_question_delete` | Delete open question |
| `/admin/quiz-test/results` | `quiz_test.results` | Submission log |
| `/admin/quiz-test/results/{id}` | `quiz_test.result_view` | Detailed submission report |
| `/admin/quiz-test/results/{id}/delete` | `entity.quiz_test_result.delete_form` | Delete submission record |
| `/admin/config/quiz-test/settings` | `quiz_test.settings` | General settings |
| `/admin/config/quiz-test/daily-report` | `quiz_test.daily_report_settings` | Daily report & schedule settings |
| `/admin/quiz-test/daily-report/today` | `quiz_test.daily_report_view_today` | View today's report (admin) |
| `/quiz-test/daily-report/{date}` | `quiz_test.daily_report_view` | Web report view by date (secured token or admin) |
| `/quiz-test/{id}` | `quiz_test.take_test` | Public test-taking form |

## Module Structure

```
quiz_test/
├── quiz_test.info.yml                  — Module metadata
├── quiz_test.module                    — Hooks (help, theme, mail, cron, entity hooks)
├── quiz_test.install                   — Schema, install/uninstall, updates (8012, 8013)
├── quiz_test.services.yml              — Service definitions (quiz_test.daily_report)
├── quiz_test.routing.yml               — Routes (CRUD, settings, reports, take test)
├── quiz_test.permissions.yml           — User permissions
├── quiz_test.links.action.yml          — Action links
├── quiz_test.links.menu.yml            — Menu links (including Daily Report)
├── quiz_test.links.task.yml            — Local tasks / tabs (Tests, Results, Settings, Daily Report)
├── quiz_test.libraries.yml             — Asset libraries
├── composer.json                       — Composer metadata
├── config/
│   └── schema/quiz_test.schema.yml     — Config schema (including daily_report)
├── css/
│   ├── quiz_test.admin.css             — Admin styles
│   └── quiz_test.form.css              — Public form styles
├── templates/
│   ├── quiz-test-results-email.html.twig   — Single submission HTML email template
│   ├── quiz-test-results-email.txt.twig    — Fallback plain text email template
│   └── quiz-test-daily-report-email.html.twig — Daily summary HTML report template
└── src/
    ├── Controller/
    │   ├── QuizTestController.php       — Questions CRUD and test-taking controller
    │   ├── QuizTestResultController.php — Submission log & single result controller
    │   └── QuizTestDailyReportController.php — Daily report web view controller
    ├── Entity/
    │   ├── QuizTest.php                 — Test content entity
    │   ├── QuizTestQuestion.php         — Question content entity
    │   ├── QuizTestOpenQuestion.php     — Open question content entity
    │   └── QuizTestResult.php           — Submission result content entity
    ├── Form/
    │   ├── QuizTestForm.php             — Test add/edit form
    │   ├── QuizTestDeleteForm.php       — Test deletion confirmation
    │   ├── QuizTestQuestionForm.php     — AJAX question add/edit form
    │   ├── QuizTestQuestionDeleteForm.php — Question deletion confirmation
    │   ├── QuizTestOpenQuestionForm.php — Open question form
    │   ├── QuizTestOpenQuestionDeleteForm.php — Open question deletion confirmation
    │   ├── QuizTestListBuilder.php      — Custom entity list builder
    │   ├── QuizTestResultFilterForm.php — Results log filter form
    │   ├── QuizTestResultDeleteForm.php — Result deletion confirmation
    │   ├── QuizTestSettingsForm.php     — General settings form
    │   ├── QuizTestDailyReportSettingsForm.php — Daily report & schedule configuration form
    │   └── TakeTestForm.php             — Public test submission form
    ├── Plugin/Mail/
    │   └── QuizTestPhpMail.php          — Custom HTML mail plugin
    └── Service/
        └── QuizTestDailyReportService.php — Reporting logic, statistics, HTML rendering & mail dispatch
```

## Mail Engine & Cron

1. **Immediate Notifications**: When a user submits a test, an HTML email is generated using `quiz_test_results_email` with full per-question results.
2. **Daily Summary Report**: Dispatched via `hook_cron()`. The system evaluates the server time against the configured `send_hour` (e.g. 20:00), ensures the report hasn't been sent yet for today, gathers all `quiz_test_result` records for the day, and emails the HTML summary report (`quiz_test_daily_report_email`).
3. **Token-Secured Web View**: Each daily email contains an authenticated URL (`/quiz-test/daily-report/YYYY-MM-DD?token=...`) allowing managers to view the responsive HTML report in any web browser without needing a Drupal account.
4. **`QuizTestPhpMail`**: Prevents Drupal core's 990-character line wrap from corrupting HTML email templates.

## Compatibility

| Drupal | PHP | Status |
|--------|-----|--------|
| 9.x  | 7.4+ | Supported |
| 10.x | 8.1+ | Supported |
| 11.x | 8.3+ | Supported |

## Current Version

**1.3.0** — see [Releases](https://github.com/Mmitekk/quiz_test/releases).

## Uninstallation

```bash
drush pm:uninstall quiz_test -y
```

## Author

- **Mmitekk** — [https://github.com/Mmitekk](https://github.com/Mmitekk)

## Links

- **Repository:** [https://github.com/Mmitekk/quiz_test](https://github.com/Mmitekk/quiz_test)
- **Releases:** [https://github.com/Mmitekk/quiz_test/releases](https://github.com/Mmitekk/quiz_test/releases)
- **Issues:** [https://github.com/Mmitekk/quiz_test/issues](https://github.com/Mmitekk/quiz_test/issues)
