const fs = require("fs");
const p = "c:/Users/user/Documents/americo_magumba/Construvasco/app/construvasco_laravel_v1/tools/bloco2_smoke_browser.mjs";
let s = fs.readFileSync(p, "utf8");
if (!s.includes("app-customer-studio mat-spinner")) {
  s = s.replace(
    "    // 4 Wizard steps 1-3\n    await page.waitForSelector('app-wizard-step-project', { timeout: 15000 });",
    "    // 4 Wizard steps 1-3\n    await page.locator('app-customer-studio mat-spinner').waitFor({ state: 'detached', timeout: 30000 }).catch(() => {});\n    await page.waitForSelector('app-wizard-step-project', { timeout: 15000 });"
  );
}
fs.writeFileSync(p, s, "utf8");
