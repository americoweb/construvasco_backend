/**
 * Smoke test Bloco 2 no browser (Playwright).
 * Uso: node tools/bloco2_smoke_browser.mjs
 * Requer: Laravel em :8000, Angular em :4200, playwright instalado (npx playwright install chromium)
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dir = dirname(fileURLToPath(import.meta.url));
const SHOTS = join(__dir, 'bloco2-screenshots');
mkdirSync(SHOTS, { recursive: true });

const BASE = 'http://127.0.0.1:4200';
const results = [];
const timings = {};

function log(step, status, detail = '', ms) {
  if (ms != null) timings[step] = ms;
  const line = { step, status, detail };
  results.push(line);
  const icon = status === 'ok' ? '✅' : status === 'warn' ? '⚠️' : '❌';
  console.log(`${icon} ${step}${detail ? ' — ' + detail : ''}`);
}

async function shot(page, name) {
  const path = join(SHOTS, `${name}.png`);
  await page.screenshot({ path, fullPage: true });
  return path;
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
    // 1 Login
    await page.goto(`${BASE}/#/auth/sign-in`, { waitUntil: 'networkidle', timeout: 60000 });
    await page.fill('#identifier', 'cliente@construvasco.co.mz');
    await page.fill('#password', 'Cliente@2026');
    await page.click('button[type="submit"]');
    await page.waitForURL(/#\/conta/, { timeout: 30000 });
    log('1. Login cliente demo', 'ok');

    // 2 Credits on dashboard
    await page.waitForSelector('app-customer-dashboard', { timeout: 15000 });
    const creditsText = await page.locator('app-credits-balance-card, mat-card').filter({ hasText: /Créditos/ }).first().textContent().catch(() => '');
    const dashCredits = await page.locator('text=Créditos IA').locator('..').textContent().catch(() => '');
    const balanceMatch = (creditsText + dashCredits).match(/\b5\b/);
    log('2. Saldo 5 créditos', balanceMatch ? 'ok' : 'warn', balanceMatch ? '' : `Texto visto: ${(dashCredits || creditsText).slice(0, 80)}`);

    // 3 CTA estudio
    const tStudio = Date.now();
    await page.goto(`${BASE}/#/conta/estudio`, { waitUntil: 'networkidle', timeout: 60000 });
    await page.locator('app-customer-studio mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});
    log('3. Ir para estudio', 'ok', `${Date.now() - tStudio}ms`);

    // 4 Wizard steps 1-3
    await page.locator('app-customer-studio mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});
    await page.waitForSelector('app-wizard-step-project', { timeout: 15000 });
    const projectStep = page.locator('app-wizard-step-project');
    await projectStep.locator('input[formcontrolname="title"]').first().fill('Casa Teste Bloco2 Smoke');
    await projectStep.locator('mat-select[formcontrolname="project_type"]').first().click();
    await page.locator('mat-option').filter({ hasText: 'Residencial' }).first().click();
    await projectStep.locator('mat-select[formcontrolname="tipologia"]').first().click();
    await page.locator('mat-option').filter({ hasText: 'T3' }).first().click();
    await page.click('button:has-text("Seguinte")');
    const briefingStep = page.locator('app-wizard-step-briefing');
    await page.waitForSelector('app-wizard-step-briefing', { timeout: 10000 });
    await briefingStep.locator('input[formcontrolname="localizacao"]').first().fill('Maputo, Costa do Sol');
    await briefingStep.locator('input[formcontrolname="area_m2"]').first().fill('180');
    await briefingStep.locator('input[formcontrolname="num_pisos"]').first().fill('2');
    await briefingStep.locator('mat-select[formcontrolname="estilo_arquitectonico"]').first().click();
    await page.locator('mat-option').first().click();
    await briefingStep.locator('mat-select[formcontrolname="paleta_acabamento"]').first().click();
    await page.locator('mat-option').first().click();
    await page.click('button:has-text("Seguinte")');
    await page.waitForSelector('app-wizard-step-references', { timeout: 10000 });
    await page.click('button:has-text("Seguinte")');
    await page.waitForSelector('app-studio-workspace', { timeout: 15000 });
        log('4. Wizard passos 1-3', 'ok');

    // 5-6 Generate mockup
    const t0 = Date.now();
    await page.click('button:has-text("Gerar mockup")');
    await page.locator('app-studio-workspace mat-spinner').waitFor({ state: 'detached', timeout: 120000 });
    await page.locator('.gallery-card img, .gallery-card .placeholder').first().waitFor({ state: 'visible', timeout: 10000 });
    const genMs = Date.now() - t0;
    const imgSrc = await page.locator('.gallery-card img').first().getAttribute('src');
    await shot(page, '06-apos-primeira-geracao');
    log('5. Gerar mockup', 'ok', `Tempo: ${(genMs / 1000).toFixed(1)}s`);
    log('6. Imagem na galeria', imgSrc ? 'ok' : 'fail', imgSrc ? `src=${imgSrc.slice(0, 60)}...` : 'sem img');

    // 7 Refine
    await page.locator('.gallery-card button:has-text("Refinar")').first().click();
    await page.locator('.refine-section textarea').fill('Mais janelas na fachada principal');
    const t1 = Date.now();
    await page.click('button:has-text("Confirmar refinamento")');
    await clickMatConfirm(page, 'Continuar');
    await page.locator('app-studio-workspace mat-spinner').waitFor({ state: 'detached', timeout: 120000 });
    await page.locator('.gallery-card img, .gallery-card .placeholder').first().waitFor({ state: 'visible', timeout: 10000 });
    const refineMs = Date.now() - t1;
    const cardCount = await page.locator('.gallery-card:not(.superseded) img').count();
    await shot(page, '07-apos-refinamento');
    log('7. Refinar com confirmação', 'ok', `Tempo: ${(refineMs / 1000).toFixed(1)}s, cards visíveis: ${cardCount}`);

    // 8 Approve first generation
    const firstApprove = page.locator('.gallery-card button:has-text("Aprovar")').first();
    await firstApprove.click();
    await clickMatConfirm(page, 'Aprovar');
    await page.waitForTimeout(2000);
    log('8. Aprovar mockup', 'ok', 'confirmar via SQL no relatório');

    // 9 Toggle superseded
    await page.locator('mat-checkbox').filter({ hasText: 'versões anteriores' }).click();
    await page.waitForTimeout(1000);
    const supersededBadge = await page.locator('text=Versão anterior').count();
    await shot(page, '09-galeria-com-superseded');
    log('9. Toggle versões anteriores', supersededBadge > 0 ? 'ok' : 'warn', `badges: ${supersededBadge}`);

    // 10 Submit
    await page.click('button:has-text("Seguinte")');
    await page.waitForSelector('text=Revisão e submissão', { timeout: 10000 });
    await page.click('button:has-text("Pedir arquitectura")');
    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {
      await clickMatConfirm(page, 'Continuar');
    }
    await page.waitForURL(/#\/conta\/pedidos\/\d+/, { timeout: 30000 });
    const submitted = await page.locator('text=Pedido submetido').count();
    await shot(page, '10-pedido-submetido');
    log('10. Submeter pedido', submitted > 0 ? 'ok' : 'warn', page.url());

    writeFileSync(join(SHOTS, 'report.json'), JSON.stringify({ results, genMs, refineMs }, null, 2));
    console.log('\nScreenshots em:', SHOTS);
  } catch (e) {
    log('FALHA', 'fail', e.message);
    await shot(page, 'error-state').catch(() => {});
    writeFileSync(join(SHOTS, 'report.json'), JSON.stringify({ results, error: e.message }, null, 2));
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
