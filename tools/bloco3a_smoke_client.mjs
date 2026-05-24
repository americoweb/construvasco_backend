/**
 * Screenshots cliente 10–13 (Playwright; ambiente ng serve :4200).
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __dir = dirname(fileURLToPath(import.meta.url));
const SHOTS = join(__dir, 'bloco3a-screenshots');
mkdirSync(SHOTS, { recursive: true });

const BASE = process.env.FRONTEND_BASE || 'http://127.0.0.1:4200';

async function loginCliente(page) {
  await page.goto(`${BASE}/`, { waitUntil: 'load', timeout: 90000 });
  await page.goto(`${BASE}/#/auth/sign-in`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('#identifier', { timeout: 90000 });
  await page.fill('#identifier', 'cliente@construvasco.co.mz');
  await page.fill('#password', 'Cliente@2026');
  await page.click('button[type="submit"]');
  await page.waitForURL(/#\/conta/, { timeout: 90000 });
}

(async () => {
  execSync('php tools/bloco3a_prep_client_smoke.php', { cwd: join(__dir, '..'), stdio: 'inherit' });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

  try {
    await loginCliente(page);

    await page.goto(`${BASE}/#/conta/pedidos`, { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Novo orçamento', { timeout: 30000 });
    await page.screenshot({ path: join(SHOTS, '10-lista-cliente-com-badge.png'), fullPage: true });

    await page.goto(`${BASE}/#/conta/pedidos/6`, { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Orçamentos recebidos', { timeout: 30000 });
    await page.waitForSelector('button:has-text("Aceitar orçamento")', { timeout: 15000 });
    await page.screenshot({ path: join(SHOTS, '11-ficha-cliente-com-orcamento.png'), fullPage: true });

    await page.getByRole('button', { name: /Aceitar orçamento/i }).first().click();
    await page.waitForSelector('text=Aceitar este orçamento', { timeout: 10000 });
    await page.screenshot({ path: join(SHOTS, '12-dialog-aceitar.png'), fullPage: true });

    await page.getByRole('button', { name: /Aceitar orçamento/i }).last().click();
    await page.waitForSelector('text=Orçamento aceite', { timeout: 30000 });
    await page.waitForTimeout(800);
    await page.screenshot({ path: join(SHOTS, '13-resultado-aceite.png'), fullPage: true });

    console.log('OK screenshots 10–13 em', SHOTS);
  } catch (e) {
    console.error('FAIL', e.message);
    await page.screenshot({ path: join(SHOTS, 'client-smoke-error.png'), fullPage: true });
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
