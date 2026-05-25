/**
 * Smoke browser Bloco 3B — checklist 1-16 + screenshots (CommonJS, igual bloco3a).
 */
const { chromium } = require('playwright');
const { mkdirSync, writeFileSync } = require('fs');
const { join } = require('path');

const SHOTS = join(__dirname, 'bloco3b-screenshots');
mkdirSync(SHOTS, { recursive: true });

const BASE = process.env.FRONTEND_BASE || 'http://127.0.0.1:4200';
const API = process.env.API_BASE || 'http://127.0.0.1:8000/api';
const PROJECT_ID = 6;

const results = [];
function record(n, ok, note = '') {
  results.push({ n, ok, note });
  console.log(`${n} ${ok ? '✅' : '❌'}${note ? ' — ' + note : ''}`);
}

async function login(page, email, password) {
  const token = await apiLogin(email, password);
  await page.goto(`${BASE}/#/auth/sign-in`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.evaluate((t) => localStorage.setItem('access_token', t), token);
  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2000);
  const home = email.includes('cliente@') ? '/#/conta/dashboard' : '/#/admin/dashboard';
  const userReady = page.waitForResponse(
    (r) => r.url().includes('/api/') && r.request().method() === 'GET' && /user|me|profile/i.test(r.url()),
    { timeout: 45000 }
  ).catch(() => null);
  await page.goto(`${BASE}${home}`, { waitUntil: 'load', timeout: 90000 });
  await userReady;
  await page.waitForTimeout(3000);
  const hash = await page.evaluate(() => window.location.hash);
  if (hash.includes('/auth/sign-in')) {
    throw new Error(`Sessão não iniciou para ${email}; hash=${hash}`);
  }
}

async function apiLogin(email, password) {
  const res = await fetch(`${API}/auth/login`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ identifier: email, password }),
  });
  if (!res.ok) throw new Error(`API login ${email}: ${res.status}`);
  const json = await res.json();
  return json.access_token || json.token;
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1360, height: 900 } });

  try {
    await login(page, 'tecnico@construvasco.co.mz', 'Tecnico@2026');
    await page.goto(`${BASE}/#/admin/projectos/${PROJECT_ID}`, { waitUntil: 'load', timeout: 60000 });
    await page.waitForSelector('text=Briefing do pedido', { timeout: 60000 });
    record(1, true, 'ficha projecto carrega');
    const hasRef =
      (await page.locator('text=Mockup aprovado').count()) > 0 ||
      (await page.locator('text=Sem mockup aprovado').count()) > 0;
    record(2, hasRef, 'mockup ou nota sem mockup');

    const submitBtn = page.getByRole('button', { name: /Submeter entregável/i });
    await submitBtn.waitFor({ state: 'visible', timeout: 30000 });
    record(3, true, 'botão submeter visível');
    if (await submitBtn.count()) {
      await submitBtn.click();
      await page.waitForSelector('mat-dialog-container', { timeout: 15000 });
      await page.locator('mat-dialog-container input').first().fill('Planta piso 0');
      const pdf = join(__dirname, 'smoke-test.pdf');
      writeFileSync(pdf, Buffer.concat([Buffer.from('%PDF-1.4\n'), Buffer.alloc(1024 * 1024, 0)]));
      await page.locator('input[type="file"]').setInputFiles(pdf);
      await page.getByRole('button', { name: /Submeter para revisão/i }).click();
      await page.waitForSelector('text=Em revisão', { timeout: 120000 });
      await page.screenshot({ path: join(SHOTS, '09-tecnico-apos-upload.png'), fullPage: true });
      record(3, true, 'upload OK');
    }

    await login(page, 'gestor@construvasco.co.mz', 'Gestor@2026');
    await page.goto(`${BASE}/#/admin/projectos/${PROJECT_ID}`, { waitUntil: 'load', timeout: 60000 });
    await page.waitForTimeout(4000);
    record(4, (await page.locator('text=Em revisão').count()) > 0);
    const aprovar = page.getByRole('button', { name: /^Aprovar$/i }).first();
    if (await aprovar.count()) {
      await aprovar.click();
      await page.waitForTimeout(3000);
    }
    await page.waitForSelector('text=Aprovado', { timeout: 60000 });
    record(5, true);
    await page.screenshot({ path: join(SHOTS, '13-gestor-apos-aprovar.png'), fullPage: true });

    const markBtn = page.getByRole('button', { name: /Marcar arquitectura entregue/i });
    record(6, (await markBtn.count()) > 0);
    if (await markBtn.count()) {
      await markBtn.click();
      await page.getByRole('button', { name: /^Confirmar$/i }).click();
      await page.waitForSelector('text=Arquitectura entregue', { timeout: 30000 });
      record(7, true);
      await page.screenshot({ path: join(SHOTS, '19-gestor-arquitectura-entregue.png'), fullPage: true });
    } else record(7, false);

    await login(page, 'cliente@construvasco.co.mz', 'Cliente@2026');
    await page.goto(`${BASE}/#/conta/projectos`, { waitUntil: 'load', timeout: 60000 });
    record(8, (await page.getByRole('link', { name: /Ver detalhes/i }).count()) > 0);

    await page.goto(`${BASE}/#/conta/projectos/${PROJECT_ID}`, { waitUntil: 'load', timeout: 60000 });
    await page.waitForTimeout(2000);
    record(9, (await page.locator('text=/Arquitectura concluída/i').count()) > 0);
    await page.screenshot({ path: join(SHOTS, '21-cliente-ficha-estado.png'), fullPage: true });
    record(10, (await page.locator('text=Planta piso 0').count()) > 0);
    record(11, (await page.getByRole('button', { name: /Descarregar/i }).count()) > 0);
    await page.screenshot({ path: join(SHOTS, '21-cliente-com-entregavel.png'), fullPage: true });

    await page.getByRole('button', { name: /Pedir orçamento de obra/i }).click();
    await page.waitForTimeout(2000);
    record(12, (await page.locator('text=/em breve|Funcionalidade/i').count()) > 0);

    await login(page, 'tecnico@construvasco.co.mz', 'Tecnico@2026');
    await page.goto(`${BASE}/#/admin/projectos/${PROJECT_ID}`, { waitUntil: 'load' });
    await page.getByRole('button', { name: /Submeter entregável/i }).click();
    await page.locator('mat-dialog-container input').first().fill('Planta cobertura');
    const pdf2 = join(__dirname, 'smoke-test2.pdf');
    writeFileSync(pdf2, Buffer.concat([Buffer.from('%PDF-1.4\n'), Buffer.alloc(512 * 1024, 0)]));
    await page.locator('input[type="file"]').setInputFiles(pdf2);
    await page.getByRole('button', { name: /Submeter para revisão/i }).click();
    await page.waitForTimeout(4000);
    record(13, true);

    const token = await apiLogin('gestor@construvasco.co.mz', 'Gestor@2026');
    const list = await (await fetch(`${API}/v1/manager/projects/${PROJECT_ID}/deliverables`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })).json();
    const pending = (list.data || []).find((d) => ['submitted_for_review', 'submitted'].includes(d.status));
    if (pending) {
      await fetch(`${API}/v1/manager/projects/${PROJECT_ID}/deliverables/${pending.id}/reject`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ rejection_reason: 'Falta planta de cobertura detalhada no desenho' }),
      });
      record(14, true);
    } else record(14, false);

    await login(page, 'tecnico@construvasco.co.mz', 'Tecnico@2026');
    await page.goto(`${BASE}/#/admin/projectos/${PROJECT_ID}`, { waitUntil: 'load' });
    await page.waitForTimeout(2000);
    record(15, (await page.locator('text=/Falta planta de cobertura/i').count()) > 0);

    await login(page, 'cliente@construvasco.co.mz', 'Cliente@2026');
    await page.goto(`${BASE}/#/conta/projectos/${PROJECT_ID}`, { waitUntil: 'load' });
    record(16, (await page.locator('text=Planta cobertura').count()) === 0);

    console.log('\nSETUP: OK');
    const failed = results.filter((r) => !r.ok);
    results.forEach((r) => console.log(`${r.n} ${r.ok ? '✅' : '❌'}${r.note ? ' — ' + r.note : ''}`));
    console.log('Screenshots:', SHOTS);
    if (failed.length) process.exitCode = 1;
  } catch (e) {
    console.error('FAIL', e.message);
    await page.screenshot({ path: join(SHOTS, 'browser-smoke-error.png'), fullPage: true }).catch(() => {});
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
