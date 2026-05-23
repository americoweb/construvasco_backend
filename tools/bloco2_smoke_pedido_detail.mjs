/**
 * Screenshot manual-like: /conta/pedidos/6?submitted=1
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dir = dirname(fileURLToPath(import.meta.url));
const SHOTS = join(__dir, 'bloco2-screenshots');
mkdirSync(SHOTS, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  try {
    await page.goto('http://127.0.0.1:4200/#/auth/sign-in', { waitUntil: 'networkidle' });
    await page.fill('#identifier', 'cliente@construvasco.co.mz');
    await page.fill('#password', 'Cliente@2026');
    await page.click('button[type="submit"]');
    await page.waitForURL(/#\/conta/, { timeout: 30000 });

    await page.goto('http://127.0.0.1:4200/#/conta/pedidos/6?submitted=1', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);
    const detail = page.locator('app-customer-request-detail').filter({ has: page.locator('h1') }).first();
    await detail.waitFor({ state: 'visible', timeout: 20000 });
    await detail.locator('mat-spinner').waitFor({ state: 'detached', timeout: 25000 }).catch(() => {});
    await detail.locator('h1, mat-card').first().waitFor({ state: 'visible', timeout: 20000 });
    await page.waitForTimeout(500);

    const checks = {
      titulo: (await detail.locator('h1').textContent())?.trim(),
      referencia: await detail.locator('text=/CV-/').count(),
      submittedMsg: await detail.locator('text=Pedido submetido').count(),
      statusLine: (await detail.locator('p.text-slate-500').first().textContent())?.trim(),
      briefingArea: await detail.locator('text=Área').count(),
      briefingData: await detail.locator('text=briefing').count(),
      mockupImg: await detail.locator('img').count(),
      localizacao: await detail.locator('text=Local:').count(),
    };
    console.log(JSON.stringify(checks, null, 2));

    await page.screenshot({ path: join(SHOTS, 'manual-pedidos-6-submitted.png'), fullPage: true });
    console.log('OK manual-pedidos-6-submitted.png');
  } catch (e) {
    console.error('FAIL', e.message);
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
