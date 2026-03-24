export async function login(page) {
  await page.goto('/wp/wp-login.php');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'admin');
  await page.click('#wp-submit');
  await page.waitForNavigation();
}

export async function dismissWelcomeModal(page) {
  // Dismiss any WordPress welcome modals that might appear
  try {
    // Wait briefly for modal to appear
    await page.waitForSelector('.components-modal__screen-overlay', { timeout: 3000 });

    // Try to close with the specific close button
    const closeButton = page.locator('.components-modal__header button.components-button[aria-label="Close"]');
    if (await closeButton.isVisible()) {
      await closeButton.click();
      // Wait for modal to disappear
      await page.waitForSelector('.components-modal__screen-overlay', { state: 'detached', timeout: 2000 });
    } else {
      // If no close button, try ESC key
      await page.keyboard.press('Escape');
      await page.waitForTimeout(500);
    }
  } catch (e) {
    // Modal not present or couldn't close, continue
  }
}
