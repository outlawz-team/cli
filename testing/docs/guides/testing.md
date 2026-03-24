# Creating and Running Tests

Radicle includes two testing frameworks to ensure your application works correctly: Pest for PHP unit and integration tests, and Playwright for end-to-end browser testing.

## Pest PHP testing

Pest provides an elegant testing experience for PHP code, including models, view composers, and application logic.

### Running Pest tests

```bash
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Models/SeedTest.php

# Run using composer script
composer test
```

### Writing Pest tests

Pest tests use a simple, expressive syntax. Create test files in the `tests/` directory with a `Test.php` suffix:

```php
<?php

use App\Models\Post;

it('can retrieve posts using eloquent methods', function () {
    $posts = Post::published()->get();

    expect($posts)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

it('post helper methods return correct types', function () {
    $post = Post::published()->first();

    if ($post) {
        expect($post->title())->toBeString()
            ->and($post->permalink())->toBeString()
            ->and($post->categories())->toBeArray();
    }
});
```

### Test organization

- **Models**: `tests/Models/` - Test Eloquent models and relationships
- **Feature**: `tests/Feature/` - Test application features and workflows
- **Unit**: `tests/Unit/` - Test individual classes and methods

### WordPress integration

Radicle's Pest setup (in `tests/Pest.php`) bootstraps WordPress and Acorn, giving you access to:

- WordPress functions and constants
- Database connections
- Eloquent models
- Acorn service container

Tests run against your development database, so avoid creating data unless you clean it up afterward.

## Playwright E2E testing

Playwright tests your application in real browsers, simulating user interactions to ensure everything works end-to-end.

### Running Playwright tests

```bash
# Run all E2E tests
npx playwright test

# Run with visible browser (helpful for debugging)
npx playwright test --headed


# Run specific test file
npx playwright test tests/e2e/example.spec.js

# Run tests on specific browser
npx playwright test --project=chromium
```

### Writing Playwright tests

Create test files in `tests/e2e/` with a `.spec.js` suffix:

```javascript
import { test, expect } from '@playwright/test';

test('front page displays hero section', async ({ page }) => {
  await page.goto('/');

  // Check for hero section
  const hero = page.locator('section').first();
  await expect(hero).toBeVisible();

  // Check for featured post content
  await expect(page.locator('h2')).toContainText('Sample Post');
});

test('navigation works correctly', async ({ page }) => {
  await page.goto('/');

  // Click on a navigation link
  await page.click('nav a[href="/about/"]');

  // Verify we're on the correct page
  await expect(page).toHaveURL('/about/');
  await expect(page.locator('h1')).toContainText('About');
});

test('forms submit successfully', async ({ page }) => {
  await page.goto('/contact/');

  // Fill out form
  await page.fill('input[name="name"]', 'Test User');
  await page.fill('input[name="email"]', 'test@example.com');
  await page.fill('textarea[name="message"]', 'This is a test message');

  // Submit form
  await page.click('button[type="submit"]');

  // Check for success message
  await expect(page.locator('.success-message')).toBeVisible();
});
```

### Playwright configuration

The `playwright.config.js` file configures:

- **Base URL**: `http://radicle.test` (your local development site)
- **Browsers**: Desktop Chrome by default
- **Timeouts**: 30 seconds per test
- **Retries**: 2 retries in CI, 0 locally
- **Traces**: Captured on first retry for debugging

## Configuration files

- `phpunit.xml` configures Pest/PHPUnit setting
- `playwright.config.js` sets browser and test settings
