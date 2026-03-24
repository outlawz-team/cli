import { test, expect } from '@playwright/test';
import { login, dismissWelcomeModal } from './helpers/loginHelper.js';

test('editor color red-500 exists', async ({ page }) => {
  await login(page);
  await page.goto("/wp/wp-admin/post-new.php");
  await dismissWelcomeModal(page);

  await page.waitForSelector('iframe[name="editor-canvas"]');
  const editorFrame = page.frameLocator('iframe[name="editor-canvas"]');

  await editorFrame
    .locator('p[aria-label="Add default block"], p.wp-block-paragraph')
    .first()
    .click();

  await editorFrame
    .locator(".editor-styles-wrapper p, p.wp-block-paragraph")
    .fill("Example text");

  // Click the color dropdown button that contains "Text" to open the color dialog
  await page.click(
    'button.components-button.block-editor-panel-color-gradient-settings__dropdown:has(div:text("Text"))'
  );

  // Wait for the dropdown to be open and click the Red (500) color button
  await page.click('button[aria-label="Red (500)"]');
});
