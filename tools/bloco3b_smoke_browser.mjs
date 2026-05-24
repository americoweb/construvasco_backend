/**
 * Screenshots Bloco 3B (pontos 5, 6, 9, 13, 19, 21).
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __dir = dirname(fileURLToPath(import.meta.url));
const SHOTS = join(__dir, 'bloco3b-screenshots');
mkdirSync(SHOTS, { recursive: true });

const BASE = process.env.FRONTEND_BASE || 'http://127.0.0.1:4200';
const API = process.env.API_BASE || 'http://127.0.0.1:8000/api';

async function login(page, email, password) {
  await page.goto(`${BASE}/#/auth/sign-in`, { waitUntil: 'load', timeout: 90000 });
  await page.waitForSelector('#identifier', { timeout: 90000 });
  await page.fill('#identifier', email);
  await page.fill('#password', password);
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);
}

async function apiLogin(email, password) {
  const res = await fetch(`${API}/auth/login`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ identifier: email, password }),
  });
  const json = await res.json();
  return json.access_token || json.token || json.data?.token;
}

(async () => {
  execSync('php tools/bloco3b_prep_smoke.php', { cwd: join(__dir, '..'), stdio: 'inherit' });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1360, height: 900 } });
  const projectId = 6;

  try {
    // Admin assigns technician (UI)
    await login(page, 'admin@construvasco.co.mz', 'Admin@2026');
    await page.goto(`${BASE}/#/admin/projectos/${projectId}`, { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Entregáveis', { timeout: 30000 });
    await page.screenshot({ path: join(SHOTS, '05-gestor-ficha-atribuicao.png'), fullPage: true });

    // Technician upload
    await page.goto(`${BASE}/#/auth/sign-in`);
    await login(page, 'tecnico@construvasco.co.mz', 'Tecnico@2026');
    await page.goto(`${BASE}/#/admin/projectos`, { waitUntil: 'networkidle' });
    await page.waitForSelector(`a[href*="/admin/projectos/${projectId}"]`, { timeout: 30000 });
    await page.screenshot({ path: join(SHOTS, '06-tecnico-lista-projecto.png'), fullPage: true });

    await page.goto(`${BASE}/#/admin/projectos/${projectId}`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Submeter entregável/i }).click();
    await page.waitForSelector('text=Submeter entregável', { timeout: 10000 });
    await page.locator('input[formcontrolname="title"]').fill('Planta piso 0');
    const fileInput = page.locator('input[type="file"]');
    const pdf = join(__dir, 'smoke-test.pdf');
    writeFileSync(pdf, Buffer.alloc(1024 * 1024, 'A'));
    await fileInput.setInputFiles(pdf);
    await page.getByRole('button', { name: /Submeter para revisão/i }).click();
    await page.waitForSelector('text=Em revisão', { timeout: 60000 });
    await page.screenshot({ path: join(SHOTS, '09-tecnico-entregavel-em-revisao.png'), fullPage: true });

    // Manager approve via API (faster) then UI
    const gestorToken = await apiLogin('gestor@construvasco.co.mz', 'Gestor@2026');
    const listRes = await fetch(`${API}/v1/manager/projects/${projectId}/deliverables`, {
      headers: { Authorization: `Bearer ${gestorToken}`, Accept: 'application/json' },
    });
    const deliverables = (await listRes.json()).data || [];
    const pending = deliverables.find((d) => d.status === 'submitted_for_review' || d.status === 'submitted');
    if (!pending) throw new Error('No pending deliverable');

    await login(page, 'gestor@construvasco.co.mz', 'Gestor@2026');
    await page.goto(`${BASE}/#/admin/projectos/${projectId}`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /^Aprovar$/i }).first().click();
    await page.waitForSelector('text=Aprovado', { timeout: 30000 });
    await page.screenshot({ path: join(SHOTS, '13-gestor-entregavel-aprovado.png'), fullPage: true });

    await page.getByRole('button', { name: /Marcar arquitectura entregue/i }).click();
    await page.getByRole('button', { name: /^Confirmar$/i }).click();
    await page.waitForSelector('text=Arquitectura entregue', { timeout: 30000 });
    await page.screenshot({ path: join(SHOTS, '19-gestor-arquitectura-entregue.png'), fullPage: true });

    // Customer
    await login(page, 'cliente@construvasco.co.mz', 'Cliente@2026');
    await page.goto(`${BASE}/#/conta/projectos/${projectId}`, { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Pedir orçamento de obra', { timeout: 30000 });
    await page.screenshot({ path: join(SHOTS, '21-cliente-ficha-com-download.png'), fullPage: true });

    console.log('OK screenshots em', SHOTS);
  } catch (e) {
    console.error('FAIL', e.message);
    await page.screenshot({ path: join(SHOTS, 'browser-smoke-error.png'), fullPage: true });
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
