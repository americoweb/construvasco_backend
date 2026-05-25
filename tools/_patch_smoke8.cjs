const fs = require("fs");
const p = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(p, "utf8");
const old = `    await page.waitForURL(/#\\/conta\\/estudio/, { timeout: 15000 });
    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {
      await clickMatConfirm(page, 'Recomeçar');
    }
    log('3. Ir para estudio', 'ok', \`\${Date.now() - tStudio}ms\`);`;
const neu = `    await page.waitForURL(/#\\/conta\\/estudio/, { timeout: 15000 });
    const draftDialog = page.locator('mat-dialog-container');
    await draftDialog.waitFor({ state: 'visible', timeout: 8000 }).catch(() => {});
    if (await draftDialog.isVisible().catch(() => false)) {
      await page.getByRole('button', { name: /Recome.ar/i }).click();
      await draftDialog.waitFor({ state: 'hidden', timeout: 30000 });
    }
    log('3. Ir para estudio', 'ok', \`\${Date.now() - tStudio}ms\`);`;
if (!s.includes("draftDialog")) {
  const idx = s.indexOf("await page.waitForURL(/#\\/conta\\/estudio/");
  const idx2 = s.indexOf("log('3. Ir para estudio'");
  if (idx === -1) throw new Error("no url wait");
  const end = s.indexOf(");", idx2) + 2;
  // replace block between url wait and log 3
  const before = s.slice(0, idx);
  const after = s.slice(s.indexOf("// 4 Wizard", idx));
  const mid = `    await page.waitForURL(/#\\/conta\\/estudio/, { timeout: 15000 });
    const draftDialog = page.locator('mat-dialog-container');
    await draftDialog.waitFor({ state: 'visible', timeout: 8000 }).catch(() => {});
    if (await draftDialog.isVisible().catch(() => false)) {
      await page.getByRole('button', { name: /Recome.ar/i }).click();
      await draftDialog.waitFor({ state: 'hidden', timeout: 30000 });
    }
    log('3. Ir para estudio', 'ok', \`\${Date.now() - tStudio}ms\`);

    `;
  s = before + mid + after;
}
fs.writeFileSync(p, s, "utf8");
console.log('done');
