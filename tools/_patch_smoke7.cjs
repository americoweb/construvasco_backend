const fs = require("fs");
const p = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(p, "utf8");
s = s.replace(
  `    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {
      await clickMatConfirm(page, 'Continuar');
    }`,
  `    if (await page.locator('mat-dialog-container').isVisible().catch(() => false)) {
      await clickMatConfirm(page, 'Recomeçar');
    }`
);
s = s.replace(
  "await page.waitForSelector('.gallery-card img', { timeout: 95000 });",
  `await page.locator('app-studio-workspace mat-spinner').waitFor({ state: 'detached', timeout: 120000 });
    await page.locator('.gallery-card img, .gallery-card .placeholder').first().waitFor({ state: 'visible', timeout: 10000 });`
);
fs.writeFileSync(p, s, "utf8");
