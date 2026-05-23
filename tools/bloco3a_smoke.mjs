/**
 * Smoke Bloco 3A — envio e aceite de orçamento (pedido #6).
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
const CLIENT = { email: 'cliente@construvasco.co.mz', password: 'Cliente@2026' };
const MANAGER = { email: 'gestor@construvasco.co.mz', password: 'Gestor@2026' };

async function login(page, creds, urlPattern) {
  await page.goto(`${BASE}/`, { waitUntil: 'load', timeout: 60000 });
  await page.goto(`${BASE}/#/auth/sign-in`, { waitUntil: 'load', timeout: 60000 });
  await page.waitForSelector('#identifier', { timeout: 60000 });
  await page.fill('#identifier', creds.email);
  await page.fill('#password', creds.password);
  await page.click('button[type="submit"]');
  await page.waitForURL(urlPattern, { timeout: 60000 });
}

(async () => {
  console.log('Prep PR#6...');
  execSync('php tools/bloco3a_prep_pr6.php', { cwd: join(__dir, '..'), stdio: 'inherit' });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

  try {
    await login(page, MANAGER, /#\/admin/);
    await page.goto(`${BASE}/#/admin/pedidos/6`, { waitUntil: 'networkidle' });
    await page.waitForSelector('app-project-request-detail h1', { timeout: 20000 });
    await page.screenshot({ path: join(SHOTS, '04-ficha-gestor-antes-orcamento.png'), fullPage: true });

    await page.getByRole('button', { name: /Enviar orçamento de arquitectura/i }).click();
    await page.waitForSelector('app-send-architecture-quote-dialog', { timeout: 10000 });
    await page.locator('app-send-architecture-quote-dialog input[formcontrolname="total_amount_mt"]').fill('75000');
    await page.locator('app-send-architecture-quote-dialog input[formcontrolname="delivery_days"]').fill('30');
    await page.locator('app-send-architecture-quote-dialog textarea[formcontrolname="conditions"]').fill('50% sinal, 50% entrega');
    await page.screenshot({ path: join(SHOTS, '06-dialog-orcamento-preenchido.png'), fullPage: true });
    await page.getByRole('button', { name: /Enviar ao cliente/i }).click();
    await page.waitForSelector('text=Enviado', { timeout: 15000 });
    await page.screenshot({ path: join(SHOTS, '07-ficha-gestor-com-orcamento.png'), fullPage: true });

    await login(page, CLIENT, /#\/conta/);
    await page.goto(`${BASE}/#/conta/pedidos`, { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Novo orçamento', { timeout: 15000 });
    await page.screenshot({ path: join(SHOTS, '12-lista-novo-orcamento.png'), fullPage: true });

    await page.goto(`${BASE}/#/conta/pedidos/6`, { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Orçamentos recebidos', { timeout: 15000 });
    await page.screenshot({ path: join(SHOTS, '13-ficha-cliente-aceitar.png'), fullPage: true });

    await page.getByRole('button', { name: /Aceitar orçamento/i }).click();
    await page.waitForSelector('text=Aceitar este orçamento', { timeout: 5000 });
    await page.getByRole('button', { name: /Aceitar orçamento/i }).last().click();
    await page.waitForSelector('text=Orçamento aceite', { timeout: 20000 });

    console.log('OK smoke 3A screenshots in', SHOTS);
  } catch (e) {
    console.error('FAIL', e.message);
    await page.screenshot({ path: join(SHOTS, 'error.png'), fullPage: true });
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
