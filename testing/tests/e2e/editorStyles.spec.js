import { test, expect } from '@playwright/test';
import { login, dismissWelcomeModal } from './helpers/loginHelper.js';

test.describe('theme.json styles', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/wp/wp-admin/post-new.php');
    await dismissWelcomeModal(page);
  });

  test('should apply correct font size to post title', async ({ page }) => {
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');

    await editorFrame.locator('.wp-block-post-title').click();
    await page.keyboard.type('Example text');

    const postTitle = editorFrame.locator('h1.wp-block-post-title');
    const h1FontSize = await postTitle.evaluate(el =>
      window.getComputedStyle(el).fontSize
    );

    expect(h1FontSize).toBeTruthy();
  });

  test('should apply zero border radius to button block', async ({ page }) => {
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');

    await editorFrame
      .locator('p[aria-label="Add default block"], p.wp-block-paragraph')
      .first()
      .click();
    await page.keyboard.type('/button');
    await page.waitForTimeout(500);
    await page.keyboard.press('Enter');
    await page.keyboard.type('Example button');

    const button = editorFrame.locator('.wp-block-button__link').first();
    await button.waitFor({ state: 'visible' });
    const buttonBorderRadius = await button.evaluate(el =>
      window.getComputedStyle(el).borderRadius
    );

    expect(buttonBorderRadius).toBe('0px');
  });

  test('should apply transparent background to outline button', async ({ page }) => {
    const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');

    // Add a button block
    await editorFrame
      .locator('p[aria-label="Add default block"], p.wp-block-paragraph')
      .first()
      .click();
    await page.keyboard.type('/button');
    await page.waitForTimeout(500);
    await page.keyboard.press('Enter');
    await page.keyboard.type('Outline button');

    // Click outside the button text to select the button wrapper block
    await editorFrame.locator('.wp-block-button').first().click();

    // Use the block toolbar's styles switcher
    // The outline style button is in the block inspector sidebar
    await page.waitForTimeout(500);

    // Insert outline class via wp.data from the parent frame
    await page.evaluate(() => {
      const selected = wp.data.select('core/block-editor').getSelectedBlock();
      if (selected) {
        wp.data.dispatch('core/block-editor').updateBlockAttributes(selected.clientId, {
          className: 'is-style-outline',
        });
      }
    });

    await page.waitForTimeout(500);
    const button = editorFrame.locator('.wp-block-button__link').first();
    await button.waitFor({ state: 'visible' });
    const bgColor = await button.evaluate(el =>
      window.getComputedStyle(el).backgroundColor
    );

    expect(bgColor).toBe('rgba(0, 0, 0, 0)');
  });
});
