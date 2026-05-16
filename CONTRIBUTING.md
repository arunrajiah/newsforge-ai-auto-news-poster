# Contributing to ArunAI – Auto News Poster

Thanks for your interest in contributing! Please take a moment to read these guidelines before opening a pull request.

## How the review process works

```
You open a PR
      │
      ▼
CI runs automatically (PHP lint · PHPCS · PHPUnit)
      │
      ├── Any job fails? ──► PR blocked, fix the failures first
      │
      └── All jobs pass? ──► 🤖 Bot auto-approves (CI green signal)
                                    │
                                    ▼
                         @arunrajiah reviews the code
                                    │
                         ┌──────────┴──────────┐
                         │                     │
                    Approved ──────────► Merged into main
                         │
                   Changes requested ──► Revise and push
```

- **Auto-approval** means the bot has confirmed CI passes. It is not a code review.
- **Final approval** from @arunrajiah is required before the PR can be merged — no exceptions.
- New commits to an open PR dismiss all existing approvals and trigger a fresh CI run.

## Development setup

```bash
git clone https://github.com/arunrajiah/ai-auto-news-poster.git
cd ai-auto-news-poster
composer install
```

| Command            | Description                              |
|--------------------|------------------------------------------|
| `composer test`    | Run PHPUnit (requires PHP ≥ 8.1)         |
| `composer lint`    | Run PHPCS (WordPress Coding Standards)   |
| `composer lint-fix`| Auto-fix PHPCS violations with phpcbf   |

## Before opening a PR

1. **Run the full suite locally** and confirm everything passes:
   ```bash
   composer lint && composer test
   ```
2. **One concern per PR** — keep changes focused. Large PRs are harder to review and more likely to introduce regressions.
3. **Follow WordPress Coding Standards** — tabs for indentation, Yoda conditions, `wp_json_encode()` over `json_encode()`, etc.
4. **Write tests** for new behaviour. The project targets ≥ 95% coverage of new code paths.
5. **Update documentation** — if your change affects user-facing behaviour, update `readme.txt` and `README.md`.
6. **PHP 7.4 compatibility** — the plugin supports PHP 7.4+. Do not use union types, named arguments, enums, or other PHP 8.x-only syntax.

## Coding standards quick reference

| Rule | Correct |
|------|---------|
| Indentation | Tabs (not spaces) |
| Conditions | `if ( null === $var )` (Yoda) |
| JSON encoding | `wp_json_encode( $data )` |
| URL parsing | `wp_parse_url( $url )` |
| Tag stripping | `wp_strip_all_tags( $str )` |
| Date formatting | `gmdate( 'Y-m-d' )` |
| SQL | `$wpdb->prepare()` for all parameterised queries |
| Output | `esc_html()`, `esc_attr()`, `esc_url()` at point of output |
| Translations | `esc_html__( 'Text', 'arunai-auto-news-poster' )` (text domain matches WP.org slug) |

## Git workflow

```bash
# Fork the repo, then:
git checkout -b feature/my-feature      # descriptive branch name
# make your changes
git push origin feature/my-feature
# open a PR against main
```

Branch naming conventions:

| Type | Pattern | Example |
|------|---------|---------|
| Feature | `feature/<slug>` | `feature/bulk-delete` |
| Bug fix | `fix/<slug>` | `fix/duplicate-detection` |
| Docs | `docs/<slug>` | `docs/scheduler-guide` |
| Refactor | `refactor/<slug>` | `refactor/image-generator` |

## Security vulnerabilities

Please **do not** open a public GitHub issue for security vulnerabilities. Instead, email the project owner directly. The vulnerability will be addressed and a patched release published before any public disclosure.

## License

By contributing, you agree that your code will be licensed under [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html), the same licence as the project.
