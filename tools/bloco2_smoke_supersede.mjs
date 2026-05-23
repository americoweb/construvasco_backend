/**
 * Smoke passos 8-9: supersede Lógica B (PR#6, gen#12).
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dir = dirname(fileURLToPath(import.meta.url));
const SHOTS = join(__dir, 'bloco2-screenshots');
mkdirSync(SHOTS, { recursive: true });

const BASE = 'http://127.0.0.1:4200';

async function login(page) {
  await page.goto(`${BASE}/#/auth/sign-in`, { waitUntil: 'networkidle', timeout: 60000 });
  await page.fill('#identifier', 'cliente@construvasco.co.mz');
  await page.fill('#password', 'Cliente@2026');
  await page.click('button[type="submit"]');
  await page.waitForURL(/#\/conta/, { timeout: 30000 });
}

async function clickMatConfirm(page, name) {
  const dialog = page.locator('mat-dialog-container');
  await dialog.waitFor({ state: 'visible', timeout: 15000 });
  await page.getByRole('button', { name }).click();
  await dialog.waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  try {
    await login(page);
    await page.goto(`${BASE}/#/conta/estudio`, { waitUntil: 'networkidle' });
    await page.locator('app-customer-studio mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});

  const studio = page.locator('.hidden.md\\:flex main').first();
    for (let i = 0; i < 5; i++) {
      if (await studio.locator('app-studio-workspace').isVisible().catch(() => false)) break;
      const btn = studio.locator('button:has-text("Seguinte")');
      if (await btn.isVisible().catch(() => false)) {
        await btn.click();
        await page.waitForTimeout(1200);
      }
    }

    await studio.locator('app-studio-workspace').waitFor({ state: 'visible', timeout: 25000 });

    // Aprovar geração visível (preferir card com imagem)
    const approveBtn = studio.locator('.gallery-card button:has-text("Aprovar")').first();
    await approveBtn.click();
    await clickMatConfirm(page, 'Aprovar');
    await page.waitForTimeout(1500);

    await studio.screenshot({ path: join(SHOTS, '08-galeria-so-aprovada.png') });
    console.log('OK screenshot: 08-galeria-so-aprovada.png');

    const visibleCards = await studio.locator('.gallery-card:not(.superseded)').count();
    console.log(`Cards visíveis (sem superseded): ${visibleCards}`);

    await studio.locator('mat-checkbox').filter({ hasText: 'versões anteriores' }).click();
    await page.waitForTimeout(1000);
    const badges = await studio.locator('text=Versão anterior').count();
    await studio.screenshot({ path: join(SHOTS, '09-galeria-com-toggle-superseded.png') });
    console.log(`OK screenshot: 09-galeria-com-toggle-superseded.png (badges: ${badges})`);
  } catch (e) {
    console.error('FAIL', e.message);
    await page.screenshot({ path: join(SHOTS, 'supersede-error.png'), fullPage: true }).catch(() => {});
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
