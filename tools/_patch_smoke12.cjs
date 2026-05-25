const fs = require("fs");
const p = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(p, "utf8");
const old = `    const tStudio = Date.now();
    await page.locator('app-customer-dashboard mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});
    await page.getByRole('link', { name: /Come.ar novo projecto/i }).click();
    await page.waitForURL(/#\\/conta\\/estudio/, { timeout: 15000 });
    const draftDialog = page.locator('mat-dialog-container');
    await draftDialog.waitFor({ state: 'visible', timeout: 8000 }).catch(() => {});
    if (await draftDialog.isVisible().catch(() => false)) {
      await page.getByRole('button', { name: /Recome.ar/i }).click();
      await draftDialog.waitFor({ state: 'hidden', timeout: 30000 });
    }
    log('3. Ir para estudio', 'ok', \`\${Date.now() - tStudio}ms\`);`;
const neu = `    const tStudio = Date.now();
    await page.goto(\`\${BASE}/#/conta/estudio\`, { waitUntil: 'networkidle', timeout: 60000 });
    await page.locator('app-customer-studio mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});
    log('3. Ir para estudio', 'ok', \`\${Date.now() - tStudio}ms\`);`;
if (s.includes("getByRole('link'")) {
  s = s.replace(/    const tStudio = Date\.now\(\);[\s\S]*?log\('3\. Ir para estudio'[^\n]+\);/, neu);
}
fs.writeFileSync(p, s, "utf8");
